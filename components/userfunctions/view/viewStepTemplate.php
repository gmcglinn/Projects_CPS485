<?php
    //Loading the page title and action buttons.
    include_once('./components/userfunctions/search/search.php');
    
    if(!isset($_SESSION)) {
        session_start();
    }
    //User has not signed in.
    if(!isset($_SESSION['user_type'])) {
        echo "<div class='w3-panel w3-margin w3-red'><p>Session Expired, Please sign in again.</p></div>";
        exit();
    }
    //User is not an admin.
    if(!($_SESSION['user_type'] == $GLOBALS['admin_type'])){
        echo "<div class='w3-panel w3-margin w3-red'><p>Error! You do not have permission to access this information.</p></div>";
        exit();
    }
    //User returned to the page after submitting changes.
    if(isset($_POST['saveStepTemplateChanges'])) {
        include_once('./backend/db_connector.php');
        //Get all user input.
        $stepTemplateID = mysqli_real_escape_string($db_conn, $_POST['stepID']);
        $title = mysqli_real_escape_string($db_conn, $_POST['stepTitle']);
        $status = mysqli_real_escape_string($db_conn, $_POST['status']);
        $fileLocation = mysqli_real_escape_string($db_conn, $_POST['fileLocation']);
        $instructions = mysqli_real_escape_string($db_conn, $_POST['instructions']);

        $sql = "UPDATE f20_step_template_table 
                    SET TSID = $status,
                    title = '$title',
                    `location` = '$fileLocation',
                    instructions = '$instructions'                     
                WHERE `SID` = $stepTemplateID";
        if ($db_conn->query($sql) === TRUE) {
            echo("<div class='w3-panel w3-margin w3-green'><p>Successfully Edited this Step Template.</p></div>");
        } 
        else {
            echo("<div class='w3-panel w3-margin w3-red'><p>Error updating the workflow: " . $db_conn->error . "</p></div>");
        }
    }
    //User chooses to remove step template.
    if(isset($_POST['remove'])) {
        $sql = "UPDATE f20_step_template_table SET TSID = 3 WHERE STPID = '$stepTemplateID'";
        if ($db_conn->query($sql) === TRUE) {
            echo("<div class='w3-panel w3-margin w3-green'><p>Successfully Removed Step Template</p></div>");
        } 
        else {
            echo("<div class='w3-panel w3-margin w3-red'><p>Error removing step template: " . $db_conn->error . "</p></div>");
        }
    }
    //Step Template ID was not sent to the page.
    if(!isset($_POST['stepTemplateID'])) {
        echo "<div class='w3-panel w3-margin w3-red'><p>Error! No step template ID recieved</p></div>";
        exit();
    }
    else {
        include_once('./backend/util.php');
        include_once('./backend/db_connector.php');

        //Gather data passed to this page.
        $stepTemplateID = mysqli_real_escape_string($db_conn, $_POST['stepTemplateID']);

        //Find all data related to the step template.
        $sql = "SELECT * FROM f20_step_template_table
                    JOIN f20_template_status_table
                        ON f20_step_template_table.TSID = f20_template_status_table.TSID
                    WHERE f20_step_template_table.STPID = '$stepTemplateID'";
        $query = mysqli_query($db_conn, $sql);
        $row = mysqli_fetch_array($query);
?>

<!-- Step Template Information -->
<div id="stepTemplateForm" class="w3-card-4 w3-padding w3-margin">
    <div class="w3-right" id="actionButtons">
        <button type="button" class="w3-button w3-blue" name="editStepTemplate" style="margin-right: 5px;" onclick="enableEdit()">Edit</button>
        <button type="button" class="w3-button w3-red" name="removeStepTemplate" onclick="removeEntry('<?php echo $stepTemplateID ?>')">Remove</button>
    </div>

    <h5>Step Template:</h5>
    <form method="post" action="./dashboard?content=view&contentType=stepTemplate">
        <input id="stepTemplateID" name="stepTemplateID" type="hidden" class="w3-input" value="<?php echo $stepTemplateID; ?>" readonly>

        <label for="stepTemplateTitle" class="w3-input">Title:</label>
        <input id="stepTemplateTitle" name="stepTemplateTitle" type="text" class="w3-input" value="<?php echo $row['2']; ?>" readonly>

        <!-- A select field so the user can only choose to edit the workflow from the list of available options -->
        <label for="status" class="w3-input">Status:</label>
        <select name="status" id="status" class="w3-input" disabled>
            <option value="<?php echo $row['1']; ?>"><?php echo $row['6']; ?></option>
            <?php
                $sql = "SELECT * FROM f20_template_status_table";
                $query = mysqli_query($db_conn, $sql);
                while($status = mysqli_fetch_array($query)) {
                    echo("<option value='" . $status['TSID'] . "'>" . $status['title'] . "</option>");
                };
            ?>
        </select>

        <label for="fileLocation" class="w3-input">File Location:</label>
        <input id="fileLocation" name="fileLocation" type="text" class="w3-input" value="<?php echo $row['4']; ?>" readonly>
        
        <label class="w3-input">Form:</label>
        <button type="button" class="w3-button w3-blue" onclick="document.getElementById('formHolder').style.display='block';">Click here to View</button>

        <label for="instructions" class="w3-input">Instructions:</label>
        <textarea name="instructions" id="instructions" class="w3-input" cols="30" rows="5" readonly>
            <?php echo $row['instructions']; ?>
        </textarea>
        <br>
        <div id="editButtons" style="display: none;">
            <button type="submit" class="w3-button w3-blue" name="saveStepTemplateChanges">Save</button>
            <button type="button" class="w3-button w3-red" onclick="disableEdit()">Cancel</button>
        </div>
    </form>
</div>

<div id="formHolder" class="w3-modal">
    <div class="w3-modal-content w3-padding" style="background-color: transparent;">
        <button class="w3-btn w3-right w3-border w3-margin w3-red" onclick="document.getElementById('formHolder').style.display='none';">&times;</button>
        <?php include_once("." . $row['4']) ?>
    </div>
</div>

<!-- Modal Pop-up to warn of deletion -->
<div id="warningHolder" class="w3-modal w3-center">
    <div class="w3-modal-content">
        <div class="w3-container w3-orange">
            <p>Warning!!</p>
            <p>'Removing' a step template will change its status to deleted!<br>A user with the appropriate permission will then need to reactivate the step template.</p>
            <p>Are you sure?
                <br>
                <form method="post" action="./dashboard.php?content=view&contentType=stepTemplate">
                    <input id="removeData" name="stepID" type="hidden">
                    <button class="w3-button w3-red" type="submit" name="remove">Yes</button>
                    <button class="w3-button w3-black" type="button" onclick="document.getElementById('warningHolder').style.display='none'">No</button>
                </form>
            </p>
        </div>
    </div>
</div>

<!-- Remove from database Script -->
<script>
    function removeEntry(stepTemplate)
    {
        //Display the warning modal.
        document.getElementById('warningHolder').style.display='block';
        //Replace hidden input data to prepare for if the user chooses to submit.
        document.getElementById('removeData').value = stepTemplate;
    }
</script>

<!-- Enable/Disable table editing Script -->
<script>
    function enableEdit()
    {
        //Disable readonly on inputs.
        var inputs = document.querySelectorAll(".w3-input");
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].readOnly=false;
            inputs[i].disabled=false;
        }
        //Hide the edit and remove buttons.
        document.getElementById("actionButtons").style.display = "none";
        //Show the save and cancel buttons.
        document.getElementById("editButtons").style.display = "inline-block";
    }
    function disableEdit()
    {
        //Re-enable readonly on all inputs.
        var inputs = document.querySelectorAll(".w3-input");
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].readOnly=true;
        }

        document.getElementById("status").disabled = true;
        //Hide the save and cancel buttons.
        document.getElementById("editButtons").style.display = "none";
        //Show the edit and remove buttons.
        document.getElementById("actionButtons").style.display = "inline-block";
    }
</script>

<?php 
    }    
?>