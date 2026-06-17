<?php 
require_once('class/DataBaseHandler.php');
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location:index.php");
    exit();
}

$dbHandler = new DataBaseHandler();
// We might need to load NotificationService here for initial history load or do it via API
// Let's do it via API or just load it here if we want server-side render for first page.
// The reference mhwun/bulksms.php loaded it directly.
require_once('class/services/NotificationService.php');

$conn = $dbHandler->pdo; // Access public PDO property directly
$notificationService = new class\services\NotificationService($conn);

// Initial History Load
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$history = $notificationService->getSMSInbox();
$totalItems = count($history);
$totalPages = ceil($totalItems / $perPage);
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$currentPageItems = array_slice($history, $offset, $perPage);

// --- Name Resolution Logic ---
$phoneToNameMap = [];
if (!empty($currentPageItems) && isset($conn)) {
    $phonesToLookup = [];
    foreach ($currentPageItems as $item) {
        if (!empty($item['receiver'])) {
            $p = $item['receiver'];
            // Normalize: use last 10 digits to match DB
            if (strlen($p) >= 10) {
                 $phonesToLookup[] = substr($p, -10);
            }
        }
    }
    
    // Batch query
    if (!empty($phonesToLookup)) {
        $phonesToLookup = array_unique($phonesToLookup);
        $placeholders = implode(',', array_fill(0, count($phonesToLookup), '?'));
        
        $sql = "SELECT MobilePhone, Fname, Lname FROM tbl_personalinfo 
                WHERE RIGHT(MobilePhone, 10) IN ($placeholders)";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_values($phonesToLookup));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $k = substr($row['MobilePhone'], -10);
                $phoneToNameMap[$k] = $row['Fname'] . ' ' . $row['Lname'];
            }
        } catch (Exception $e) {
            error_log("Name Resolution Error: " . $e->getMessage());
        }
    }
}

// Balance
$balance = $notificationService->getSMSBalance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bulk SMS - NASU, OOUTH</title>
    <?php include('includes/header.php'); ?>
    <style>
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; margin-bottom: 1.5rem; }
        .card-header { background-color: #fff; border-bottom: 1px solid #edf2f7; font-weight: 600; }
        .sms-counter { font-size: 0.85rem; color: #6c757d; }
        .search-results {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius);
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            display: none;
        }
        .search-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
        }
        .search-item:hover { background: rgba(34,197,94,0.08); }
        .search-item:last-child { border-bottom: none; }
    </style>
</head>
<body id="body-pd">
    <?php include('includes/header_nav.php'); ?>
    
    <?php include "includes/sidebar2.php"; ?>

    <main class="top-margin">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3" style="border-bottom:1px solid var(--border-light);">
                    <h5 style="font-family:var(--font-mono);font-weight:700;margin:0;">Bulk SMS Center</h5>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                             <button type="button" class="btn btn-sm" style="border:1px solid var(--border-light);color:var(--text-secondary);">Balance: ₦<?php echo number_format($balance, 2); ?></button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- SMS Composition Area -->
                    <div class="col-lg-8">
                        
                        <!-- Search & Add -->
                        <div class="card">
                            <div class="card-header py-3">
                                <h5 class="mb-0"><i class='bx bx-user-plus'></i> Search & Add Recipients</h5>
                            </div>
                            <div class="card-body">
                                <div class="position-relative">
                                    <input type="text" id="memberSearch" class="form-control form-control-lg" placeholder="Search by Name or Staff ID..." autocomplete="off">
                                    <div id="searchResults" class="search-results"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Compose Message -->
                         <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class='bx bx-message-edit'></i> Compose Message</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Recipients <span class="text-muted fw-normal" style="font-size: 0.8rem;">(Separate multiple numbers with commas)</span></label>
                                    <div class="input-group mb-2">
                                        <textarea id="recipientList" class="form-control font-monospace" rows="2" placeholder="e.g. 08012345678, 09098765432"></textarea>
                                        <button class="btn btn-outline-danger" type="button" onclick="clearRecipients()" title="Clear All"><i class='bx bx-trash'></i></button>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Total Recipients: <span id="recipientCount" class="fw-bold text-primary">0</span></small>
                                        
                                        <div>
                                            <button class="btn btn-sm btn-success" onclick="addAllContacts()"><i class='bx bx-group'></i> Add All Active Members</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Message Content</label>
                                    <textarea id="smsMessage" class="form-control" rows="5" placeholder="Type your message here..."></textarea>
                                    <div class="d-flex justify-content-between mt-1 sms-counter">
                                        <span>Characters: <b id="charCount">0</b></span>
                                        <span>Pages: <b id="pageCount">0</b> (~160 chars/page)</span>
                                    </div>
                                </div>

                                <button onclick="sendBulkSMS()" id="btnSend" class="btn btn-primary btn-lg w-100"><i class='bx bx-paper-plane'></i> Send Broadcast</button>
                            </div>
                        </div>

                    </div>

                    <!-- Sidebar / Info / History -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0" style="color:var(--text-muted);">Usage Guidelines</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0 small" style="color:var(--text-secondary);">
                                    <li class="mb-2"><i class='bx bx-check-circle' style="color:var(--accent);"></i> Numbers are automatically formatted.</li>
                                    <li class="mb-2"><i class='bx bx-check-circle' style="color:var(--accent);"></i> Separate multiple numbers with commas.</li>
                                    <li class="mb-2"><i class='bx bx-error' style="color:var(--accent-amber);"></i> Avoid special characters.</li>
                                    <li><i class='bx bx-info-circle' style="color:var(--accent-blue);"></i> Sender ID is fixed.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                             <div class="card-header">
                                <h5 class="mb-0"><i class='bx bx-history'></i> Recent History</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                                        <thead>
                                            <tr>
                                                <th>Receiver</th>
                                                <th>Message</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($currentPageItems)): ?>
                                                <?php foreach ($currentPageItems as $msg): ?>
                                                    <?php 
                                                        $rawPhone = $msg['receiver'] ?? '';
                                                        $searchKey = strlen($rawPhone) >= 10 ? substr($rawPhone, -10) : $rawPhone;
                                                        $displayName = $phoneToNameMap[$searchKey] ?? $rawPhone;
                                                        $hasName = isset($phoneToNameMap[$searchKey]);
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($displayName); ?></div>
                                                            <?php if ($hasName && $displayName !== $rawPhone): ?>
                                                                <small class="text-muted"><?php echo htmlspecialchars($rawPhone); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($msg['message'] ?? ''); ?>">
                                                                <?php echo htmlspecialchars($msg['message'] ?? '-'); ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                $status = strtolower($msg['status'] ?? 'unknown');
                                                                $badgeClass = match($status) {
                                                                    'delivered' => 'bg-success',
                                                                    'sent' => 'bg-primary',
                                                                    'pending' => 'bg-warning text-dark',
                                                                    'failed', 'dnd', 'rejected' => 'bg-danger',
                                                                    default => 'bg-secondary'
                                                                };
                                                            ?>
                                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?php echo isset($msg['created_at']) ? date('M d, H:i', strtotime($msg['created_at'])) : '-'; ?></small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center text-muted py-3">No history found</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                             <?php if ($totalPages > 1): ?>
                            <div class="card-footer d-flex justify-content-between" style="background:var(--bg-sidebar);border-top:1px solid var(--border-light);">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-sm btn-outline-secondary">Prev</a>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>
                                <small>Page <?php echo $page; ?> of <?php echo $totalPages; ?></small>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-sm btn-outline-secondary">Next</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

    </main>

    <?php include("includes/nav_script.php"); ?>
    
    <script>
        $(document).ready(function() {
            
            // Search Logic
            let searchTimeout;
            $('#memberSearch').on('input', function() {
                const term = $(this).val();
                clearTimeout(searchTimeout);
                const resultsBox = $('#searchResults');

                if (term.length < 3) {
                    resultsBox.hide().empty();
                    return;
                }

                searchTimeout = setTimeout(() => {
                    $.post('bulksms_api.php', { action: 'search_members', term: term }, function(res) {
                        resultsBox.empty().show();
                        
                        if (res.status === 'success' && res.data.length > 0) {
                            res.data.forEach(m => {
                                const item = `
                                    <div class="search-item" onclick="addContact('${m.MobilePhone}', '${m.fullname}')">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">${m.fullname}</div>
                                                <small class="text-muted">${m.staff_id} | ${m.MobilePhone}</small>
                                            </div>
                                            <i class='bx bx-plus-circle text-primary fs-4'></i>
                                        </div>
                                    </div>
                                `;
                                resultsBox.append(item);
                            });
                        } else {
                            resultsBox.append('<div class="p-3 text-center text-muted">No members found.</div>');
                        }
                    }, 'json');
                }, 300);
            });

            // Hide search on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#memberSearch, #searchResults').length) {
                    $('#searchResults').hide();
                }
            });

            // Counter Logic
            $('#recipientList').on('input', function() {
                const text = $(this).val();
                const count = text.split(',').filter(s => s.trim().length > 0).length;
                $('#recipientCount').text(count);
            });

            $('#smsMessage').on('input', function() {
                const len = $(this).val().length;
                $('#charCount').text(len);
                let pages = 1;
                if(len > 160) {
                    pages = Math.ceil(len / 153);
                }
                $('#pageCount').text(pages);
            });
        });

        function addContact(phone, name) {
            if (!phone) return;
            const currentVal = $('#recipientList').val();
            const newVal = currentVal ? (currentVal + ', ' + phone) : phone;
            $('#recipientList').val(newVal).trigger('input');
            
            $('#memberSearch').val('');
            $('#searchResults').hide().empty();
            
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            Toast.fire({ icon: 'success', title: `Added ${name}` });
        }

        function clearRecipients() {
            $('#recipientList').val('').trigger('input');
        }

        function addAllContacts() {
            Swal.fire({
                title: 'Loading Contacts...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post('bulksms_api.php', { action: 'fetch_all_contacts' }, function(res) {
                Swal.close();
                if (res.status === 'success') {
                    const currentVal = $('#recipientList').val();
                    const newNumbers = res.data.join(', ');
                    const finalVal = currentVal ? (currentVal + ', ' + newNumbers) : newNumbers;
                    $('#recipientList').val(finalVal).trigger('input');
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Added ${res.count} contacts`,
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    Swal.fire('Error', 'Failed to fetch contacts', 'error');
                }
            }, 'json').fail(function() {
                Swal.close();
                Swal.fire('Error', 'Server Error', 'error');
            });
        }

        function sendBulkSMS() {
            const recipients = $('#recipientList').val();
            const message = $('#smsMessage').val();
            const count = $('#recipientCount').text();

            if (!recipients.trim()) {
                Swal.fire('Attention', 'Please add at least one recipient.', 'warning');
                return;
            }
            if (!message.trim()) {
                Swal.fire('Attention', 'Please enter a message.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Send Broadcast?',
                text: `You are about to send to approx ${count} recipients.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Send Now',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.post('bulksms_api.php', {
                        action: 'send_bulk_sms',
                        recipients: recipients,
                        message: message
                    }, null, 'json')
                    .then(response => {
                        if (response.status !== 'success') {
                            throw new Error(response.message || 'Unknown error');
                        }
                        return response;
                    })
                    .catch(error => {
                         Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sent!',
                        text: result.value.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload(); // Reload to update history and balance
                    });
                }
            });
        }
    </script>
</body>
</html>
