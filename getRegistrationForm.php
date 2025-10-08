<?php


require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
if (!isset($_GET['staff_id'])) {
    $_GET['staff_id'] = -1;
}
//Nok relationionship
$noks = $dbHandler->getSelectItems('nok_relationship', 'nok_id', 'relationship');
$states = $dbHandler->getSelectItems('state_nigeria', 'stateid', 'state');
$staff_id = filter_var($_GET['staff_id'], FILTER_VALIDATE_INT);
if ($staff_id === false) {
    throw new Exception('Invalid search value provided.');
}
function getAndDisplayStaffId($result, $defaultDisplay = "")
{
    // Use the ternary operator to return an empty string if $staffNo is 0, otherwise return $staffNo
    return $result == 0 ? $defaultDisplay : $result;
}

?>

<form method=" POST" id="registrationForm" name="registrationForm">
    <div class="form-group">
        <label for="staff_no">Staff No:</label>
        <input type="text" class="form-control" name="staff_no" id="staff_no" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'staff_id', 'staff_id', $staff_id)); ?>">
    </div>
    <div class="form-group">
        <?php $title = getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'title', 'staff_id', $staff_id)); ?>

        <label for="sfxname">Title:</label>
        <select name="sfxname" id="sfxname" class="form-control">
            <option value="na">-Select-</option>
            <option value="Mr" <?php if ($title == "Mr") {
                                    echo "selected";
                                } ?>>Mr</option>
            <option value="Miss" <?php if ($title == "Miss") {
                                        echo "selected";
                                    } ?>>Miss</option>
            <option value="Mrs" <?php if ($title == "Mrs") {
                                    echo "selected";
                                } ?>>Mrs</option>
            <option value="Dr" <?php if ($title == "Dr") {
                                    echo "selected";
                                } ?>>Dr</option>
            <option value="Baby" <?php if ($title == "Baby") {
                                        echo "selected";
                                    } ?>>Baby</option>
            <option value="Master" <?php if ($title == "Master") {
                                        echo "selected";
                                    } ?>>Master</option>
        </select>
    </div>
    <div class="form-group">
        <label for="Fname">First Name <span class="text-danger">*</span></label>
        <input type="text" name="Fname" id="Fname" class="form-control" placeholder="Enter first name" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'Fname', 'staff_id', $staff_id)); ?>">
    </div>

    <div class="form-group">
        <label for="Mname">Middle Name:</label>
        <input type="text" class="form-control" id="Mname" name="Mname" placeholder="Enter middle name" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'Mname', 'staff_id', $staff_id)); ?>">

    </div>

    <div class="form-group">
        <label for="Lname">Last Name:<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="Lname" name="Lname" placeholder="Enter last name" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'Lname', 'staff_id', $staff_id)); ?>">

    </div>
    <?php $gender = getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'gender', 'staff_id', $staff_id)); ?>
    <div class="form-group">
        <legend class="col-form-label  pt-0">Gender:<span class="text-danger">*</span></legend>

        <div class="form-check">
            <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" <?php if ($gender == "Male") {
                                                                                                        echo "checked";
                                                                                                    } ?>>
            <label class="form-check-label" for="genderMale">Male</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" <?php if ($gender == "Female") {
                                                                                                            echo "checked";
                                                                                                        } ?>>
            <label class="form-check-label" for="genderFemale">Female</label>
        </div>
    </div>


    <div class="form-group">
        <label for="DOB">Date of Birth [mm/dd/yyyy]:<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="DOB" name="DOB" readonly value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'DOB', 'staff_id', $staff_id)); ?>">

    </div>

    <div class="form-group">
        <label for="Address">House No.:<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="Address" name="Address" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'Address', 'staff_id', $staff_id)); ?>">

    </div>

    <div class="form-group">
        <label for="Address2">Address 2:</label>
        <input type="text" class="form-control" id="Address2" name="Address2" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'Address2', 'staff_id', $staff_id)); ?>">
    </div>

    <div class="form-group">
        <label for="City">City:<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="City" name="City" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'City', 'staff_id', $staff_id), "Sagamu"); ?>">

    </div>

    <div class="form-group">
        <?php $state_id = getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'state_id', 'staff_id', $staff_id)); ?>
        <label for="State">State:<span class="text-danger">*</span></label>
        <select name="State" id="State" class="form-select">
            <option value="" selected>Select...</option>
            <?php
            foreach ($states as $state) {
                echo '<option value="' . $state['stateid'] . '"';
                if ($state_id == '') {
                    $state_id = 28;
                }
                if ($state['stateid'] == $state_id) {
                    echo 'selected';
                }
                echo ' >' . $state['state'] . '</option>';
            }
            ?>
        </select>
    </div>

    <!-- Mobile Phone Field -->
    <div class="form-group">
        <label for="MobilePhone">Mobile Phone:<span class="text-danger">*</span></label>

        <input type="text" class="form-control" name="MobilePhone" id="MobilePhone" placeholder="Enter mobile phone" maxlength="11" minlength="11" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'MobilePhone', 'staff_id', $staff_id), 0); ?>">

    </div>

    <!-- Email Address Field -->
    <div class="form-group">
        <label for="EmailAddress">E-mail Address:</label>
        <input type="email" class="form-control" name="EmailAddress" id="EmailAddress" placeholder="Enter email address" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'EmailAddress', 'staff_id', $staff_id)); ?>">
    </div>
    <div class="form-group">
        <?php $Status = getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_personalinfo', 'Status', 'staff_id', $staff_id)); ?>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="statusToggle" id="statusToggle" <?php if ($Status == 1) {
                                                                                                        echo "checked";
                                                                                                    } ?>>
            <label class="form-check-label" for="statusToggle">Active Status</label>
        </div>
    </div>



    <div>
        <div class="card">
            <h5 class="card-header">Next of Kin</h5>
            <div class="card-body">
                <div class="form-group">
                    <label for="NOkName">Next of Kin:<span class="text-danger">*</span></label>

                    <input type="text" class="form-control" name="NOkName" id="NOkName" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_nok', 'NOkName', 'staff_id', $staff_id)); ?>">

                </div>
                <div class="form-group">
                    <label for="NOKRelationship">Relationship:<span class="text-danger">*</span></label>
                    <?php $NOKRelationship = getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_nok', 'NOKRelationship', 'staff_id', $staff_id)); ?>

                    <select name="NOKRelationship" id="NOKRelationship" class="form-select">
                        <option value="na" selected>Select...</option>
                        <?php
                        foreach ($noks as $nok) {
                            echo '<option value="' . $nok['nok_id'] . '"';
                            if ($NOKRelationship == $nok['nok_id']) {
                                echo "selected ";
                            }
                            echo ' >' . $nok['relationship'] . '</option>';
                        }
                        ?>
                    </select>

                </div>

                <div class="form-group">
                    <label for="NOKPhone">Next of Kin Phone No:<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NOKPhone" id="NOKPhone" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_nok', 'NOKPhone', 'staff_id', $staff_id)); ?>">
                </div>

                <div class="form-group">
                    <label for="NOKAddress">Next of Kin Address:<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NOKAddress" id="NOKAddress" value="<?= getAndDisplayStaffId($staffNo = $dbHandler->getSingleItem('tbl_nok', 'NOKAddress', 'staff_id', $staff_id)); ?>">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="same" name="same" value="checked">
                        <label class="form-check-label" for="same">Same as above</label>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Additional form fields here -->
    <div class="form-group">
        <button type="submit" class="btn btn-primary" name="Submit" value="Save">Save</button>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#DOB').datepicker({
            autoclose: true,
            endDate: '0',
            todayHighlight: true
        });
    });
</script>
<script>
    $(document).ready(function() {

        $("#search").on('focus', function() {
            $("#search").select();
        })

        $('#registrationForm').on('submit', function(event) {
            // Initially, no errors
            let hasErrors = false;

            // Clear previous error messages
            $('.error-message').remove();
            $('.form-control').removeClass('is-invalid');

            // Example validations:

            // Check if "Staff No" is filled in
            if ($('#staff_no').val().trim() === '') {
                $('#staff_no').addClass('is-invalid').after('<div class="error-message text-danger">Staff No is required.</div>');
                hasErrors = true;
            }

            // Validate "First Name" is filled in
            if ($('#Fname').val().trim() === '') {
                $('#Fname').addClass('is-invalid').after('<div class="error-message text-danger">First Name is required.</div>');
                hasErrors = true;
            }

            // Validate "Last Name" is filled in
            if ($('#Lname').val().trim() === '') {
                $('#Lname').addClass('is-invalid').after('<div class="error-message text-danger">Last Name is required.</div>');
                hasErrors = true;
            }

            // Validate "Address" is filled in
            if ($('#Address').val().trim() === '') {
                $('#Address').addClass('is-invalid').after('<div class="error-message text-danger">Address is required.</div>');
                hasErrors = true;
            }

            // Validate "Address" is filled in
            if ($('#City').val().trim() === '') {
                $('#City').addClass('is-invalid').after('<div class="error-message text-danger">City is required.</div>');
                hasErrors = true;
            }

            // Validate "State" is filled in
            if ($('#State').val().trim() === '') {
                $('#State').addClass('is-invalid').after('<div class="error-message text-danger">State is required.</div>');
                hasErrors = true;
            }


            // Validate "State" is filled in
            if ($('#MobilePhone').val().trim() === '') {
                $('#MobilePhone').addClass('is-invalid').after('<div class="error-message text-danger">MobilePhone is required.</div>');
                hasErrors = true;
            }

            // Validate Email Format
            if ($('#EmailAddress').val() !== '') {
                let emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
                if (!emailReg.test($('#EmailAddress').val())) {
                    $('#EmailAddress').addClass('is-invalid').after('<div class="error-message text-danger">Enter a valid email address.</div>');
                    hasErrors = true;
                }
            }


            // Validate "NOkName" is filled in
            if ($('#NOkName').val().trim() === '') {
                $('#NOkName').addClass('is-invalid').after('<div class="error-message text-danger">Next Of kin Name is required.</div>');
                hasErrors = true;
            }

            // Validate "NOKPhone" is filled in
            if ($('#NOKPhone').val().trim() === '') {
                $('#NOKPhone').addClass('is-invalid').after('<div class="error-message text-danger">Next Of Kin Phone is required.</div>');
                hasErrors = true;
            }

            // If any errors were found, prevent form from submitting
            if (hasErrors) {
                event.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 200);
            } else {
                $('#overlay').fadeIn();
                // Here, you might want to also include an AJAX call to check if the "Staff No" exists
                // For now, we'll assume it doesn't to proceed with submitting the form (which you'd replace with an AJAX call in reality)
                event.preventDefault(); // Remove this line when AJAX call is implemented
                var formData = $(this).serialize(); // Serializes form data for Ajax

                var firstname = $('#Fname').val();
                // Ajax submission
                $.ajax({
                    type: 'POST',
                    url: 'saveFormData.php', // Adjust if necessary
                    data: formData,
                    success: function(response) {

                        if (response == 1) { // Display response from the server
                            // Handle success. For example, display a success message
                            // alert('Form submitted successfully.');
                            displayAlert(firstname + ' info saved successfully', 'center', 'success')
                            $('#registrationForm')[0].reset();
                            $("#registration").load('getRegistrationForm.php?staff_id=-1')
                        } else {
                            displayAlert('Error saving', 'center', 'error')
                        }
                    },
                    error: function() {
                        // Handle error
                        displayAlert('Error saving', 'center', 'error')
                    }
                });
                $('#overlay').fadeOut();

            }
        });

        $('#staff_no').blur(function() {
            var staffNo = $(this).val();
            $.ajax({
                url: 'class/check_staff_no.php',
                type: 'POST',
                data: {
                    staffNo: staffNo
                },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        alert('Staff No already exists.');
                        // Invalidate the field, e.g., by adding a visual cue or message
                        $('#staff_no').addClass('is-invalid');
                    } else {
                        $('#staff_no').removeClass('is-invalid');
                    }
                }
            });
        });

        function displayAlert1(message, type) {
            var alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
            $('#alert-placeholder').html(alertHtml);
            $('html, body').animate({
                scrollTop: $('#alert-placeholder').first().offset().top - 100
            }, 200);
        }

        $('#same').change(function() {
            if (this.checked) {
                var address = $('#Address').val();
                var address2 = $('#Address2').val();
                var city = $('#City').val();
                var state = $('#State option:selected').text(); // Assuming you want the text not the value

                // Concatenate the address fields if needed or assign individually
                $('#NOKAddress').val(address + ' ' + address2 + ' ' + city + ' ' + state);
            } else {
                $('#NOKAddress').val(''); // Clear the Next of Kin address if unchecked
            }
        });

    });
</script>


<?php include("includes/nav_script.php"); ?>