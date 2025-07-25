<?php
include("../../includes/header.php");

$userLoggedIn = $_SESSION['username'];
$profile_id = $user['username'];
$imgSrc = "";
$result_path = "";
$msg = "";

// 0 - Remove The Temp image if it exists
if (!isset($_POST['x']) && !isset($_FILES['image']['name'])) {
    $temppath = '../../assets/images/profile_pics/' . $profile_id . '_temp.jpeg';
    if (file_exists($temppath)) {
        @unlink($temppath);
    }
}

if (isset($_FILES['image']['name'])) {
    // 1 - Upload Original Image To Server
    $ImageName = $_FILES['image']['name'];
    $ImageTempName = $_FILES['image']['tmp_name'];
    $ImageType = @explode('/', $_FILES['image']['type']);
    $type = $ImageType[1]; // file type

    // Set Upload directory (physique)
    $uploaddir = '../../assets/images/profile_pics';
    // Set File name
    $file_name = $profile_id . '_temp.jpeg';
    $fullpath = $uploaddir . "/" . $file_name;

    // Move the file to correct location
    $move = move_uploaded_file($ImageTempName, $fullpath);
    chmod($fullpath, 0777);

    if (!$move) {
        $msg = "File didn't upload";
    } else {
        $imgSrc = "/Facebook-clone/assets/images/profile_pics/" . $file_name; // chemin web pour affichage
        $msg = "Upload Complete!";
        $src = $file_name; // pour le crop
    }

    // 2 - Resize The Image To Fit In Cropping Area
    clearstatcache();
    $original_size = getimagesize($fullpath);
    $original_width = $original_size[0];
    $original_height = $original_size[1];
    $main_width = 500;
    $main_height = $original_height / ($original_width / $main_width);

    if ($_FILES["image"]["type"] == "image/gif") {
        $src2 = imagecreatefromgif($fullpath);
    } elseif ($_FILES["image"]["type"] == "image/jpeg" || $_FILES["image"]["type"] == "image/pjpeg") {
        $src2 = imagecreatefromjpeg($fullpath);
    } elseif ($_FILES["image"]["type"] == "image/png") {
        $src2 = imagecreatefrompng($fullpath);
    } else {
        $msg .= " There was an error uploading the file. Please upload a .jpg, .gif or .png file.";
    }

    $main = imagecreatetruecolor($main_width, $main_height);
    imagecopyresampled($main, $src2, 0, 0, 0, 0, $main_width, $main_height, $original_width, $original_height);
    imagejpeg($main, $fullpath, 90);
    chmod($fullpath, 0777);
    imagedestroy($src2);
    imagedestroy($main);
}

// 3- Cropping & Converting The Image To Jpg
if (isset($_POST['x'])) {
    $type = $_POST['type'];
    $src = '../../assets/images/profile_pics/' . $_POST['src'];
    $finalname = $profile_id . md5(time());

    $targ_w = $targ_h = 150;
    $jpeg_quality = 90;

    if ($type == 'jpg' || $type == 'jpeg' || $type == 'JPG' || $type == 'JPEG') {
        $img_r = imagecreatefromjpeg($src);
    } elseif ($type == 'png' || $type == 'PNG') {
        $img_r = imagecreatefrompng($src);
    } elseif ($type == 'gif' || $type == 'GIF') {
        $img_r = imagecreatefromgif($src);
    } else {
        $img_r = imagecreatefromjpeg($src); // fallback
    }

    $dst_r = imagecreatetruecolor($targ_w, $targ_h);
    imagecopyresampled(
        $dst_r, $img_r,
        0, 0,
        $_POST['x'], $_POST['y'],
        $targ_w, $targ_h,
        $_POST['w'], $_POST['h']
    );
    $final_path = '../../assets/images/profile_pics/' . $finalname . '.jpeg';
    imagejpeg($dst_r, $final_path, $jpeg_quality);

    imagedestroy($img_r);
    imagedestroy($dst_r);
    @unlink($src);

    $result_path = "assets/images/profile_pics/" . $finalname . ".jpeg";
    // Met à jour la base
    $insert_pic_query = mysqli_query($con, "UPDATE users SET profile_pic='$result_path' WHERE username='$userLoggedIn'");

    // Redirige vers settings pour voir la nouvelle photo
    header("Location: settings.php");
    exit();
}
?>
<div id="Overlay" style=" width:100%; height:100%; border:0px #990000 solid; position:absolute; top:0px; left:0px; z-index:2000; display:none;"></div>
<div class="main_column column">

    <div id="formExample">
        <p><b> <?= $msg ?> </b></p>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            Upload a new profile picture<br /><br />
            <input type="file" id="image" name="image" style="width:200px; height:30px;" required /><br /><br />
            <input type="submit" value="Submit" style="width:85px; height:25px;" />
        </form><br /><br />
    </div> <!-- Form-->

    <?php if ($imgSrc) { ?>
        <script>
            $('#Overlay').show();
            $('#formExample').hide();
        </script>
        <div id="CroppingContainer" style="width:800px; max-height:600px; background-color:#FFF; margin-left: -200px; position:relative; overflow:hidden; border:2px #666 solid; z-index:2001; padding-bottom:0px;">
            <div id="CroppingArea" style="width:500px; max-height:400px; position:relative; overflow:hidden; margin:40px 0px 40px 40px; border:2px #666 solid; float:left;">
                <img src="<?= $imgSrc ?>" border="0" id="jcrop_target" style="border:0px #990000 solid; position:relative; margin:0px 0px 0px 0px; padding:0px;" />
            </div>
            <div id="InfoArea" style="width:180px; height:150px; position:relative; overflow:hidden; margin:40px 0px 0px 40px; border:0px #666 solid; float:left;">
                <p style="margin:0px; padding:0px; color:#444; font-size:18px;">
                    <b>Crop Profile Image</b><br /><br />
                    <span style="font-size:14px;">
                        Crop / resize your uploaded profile image.<br />
                        Once you are happy with your profile image then please click save.
                    </span>
                </p>
            </div>
            <br />
            <div id="CropImageForm" style="width:100px; height:30px; float:left; margin:10px 0px 0px 40px;">
                <form action="upload.php" method="post" onsubmit="return checkCoords();">
                    <input type="hidden" id="x" name="x" />
                    <input type="hidden" id="y" name="y" />
                    <input type="hidden" id="w" name="w" />
                    <input type="hidden" id="h" name="h" />
                    <input type="hidden" value="jpeg" name="type" />
                    <input type="hidden" value="<?= $file_name ?>" name="src" />
                    <input type="submit" value="Save" style="width:100px; height:30px;" />
                </form>
            </div>
            <div id="CropImageForm2" style="width:100px; height:30px; float:left; margin:10px 0px 0px 40px;">
                <form action="upload.php" method="post" onsubmit="return cancelCrop();">
                    <input type="submit" value="Cancel Crop" style="width:100px; height:30px;" />
                </form>
            </div>
        </div><!-- CroppingContainer -->
    <?php } ?>
</div>
<?php if ($result_path) { ?>
    <img src="/Facebook-clone/<?= $result_path ?>" style="position:relative; margin:10px auto; width:150px; height:150px;" />
<?php } ?>
<br /><br />
