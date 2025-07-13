<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: #f9f9fb;
  color: #333;
  padding: 30px;
  max-width: 700px;
  margin: auto;
}

h2, h3 {
  color: #2c3e50;
}

form {
  background: #fff;
  border: 1px solid #ddd;
  padding: 20px;
  border-radius: 6px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

label {
  display: block;
  margin-bottom: 18px;
  font-weight: 500;
}

input[type="text"] {
  width: 100%;
  padding: 12px 14px;
  font-size: 15px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-sizing: border-box;
  background-color: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

input[type="text"]:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
  outline: none;
}

button {
  padding: 10px 18px;
  margin-top: 10px;
  margin-right: 10px;
  font-size: 14px;
  border: none;
  border-radius: 4px;
  background-color: #3498db;
  color: #fff;
  cursor: pointer;
  transition: background-color 0.2s;
}

button:hover {
  background-color: #2980b9;
}

#debug {
  white-space: pre-wrap;
  background: #fcfcfc;
  padding: 12px;
  border: 1px solid #bbb;
  border-left: 4px solid #888;
  font-family: monospace;
  color: #444;
  margin-top: 20px;
  border-radius: 4px;
}

img {
  margin-top: 20px;
  max-width: 220px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
</style>

<?php
/* ------------- helpers ------------- */
$debug = [];
function log_debug(string $m): void { global $debug; $debug[] = $m; }

function has_transparency($im): bool {
    $w = imagesx($im); $h = imagesy($im);
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if ((imagecolorat($im, $x, $y) & 0x7F000000) >> 24) return true;
        }
    }
    return false;
}

/* ------------- main logic ------------- */
$gifData = null; 
$fileExists = false; 
$showPreview = false; 
$finalName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_url'])) {
    $url = filter_var(trim($_POST['image_url']), FILTER_VALIDATE_URL);
    $inputName = trim($_POST['filename'] ?? '');

    if (!$url) {
        log_debug('❌ Invalid URL');
    } else {
        log_debug('📥 Downloading: ' . $url);
        $imgBin = @file_get_contents($url);
        if ($imgBin === false) {
            log_debug('❌ Download failed');
        } else {
            // Check if URL ends with .gif (case insensitive)
            $path = parse_url($url, PHP_URL_PATH);
            $isGif = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'gif';

            if ($isGif) {
                // Use downloaded GIF data directly without reprocessing
                $gifData = $imgBin;
                $base = $inputName ? pathinfo($inputName, PATHINFO_FILENAME)
                                   : pathinfo($path, PATHINFO_FILENAME);
                $finalName = $base . '.gif';
                $showPreview = true;
                log_debug('ℹ️ Input is GIF — skipping re-encoding.');
            } else {
                $src = @imagecreatefromstring($imgBin);
                if (!$src) {
                    log_debug('❌ Unsupported/corrupt image');
                } else {
                    $w = imagesx($src); 
                    $h = imagesy($src);
                    $hasAlpha = has_transparency($src);
                    log_debug('🔍 Transparency: ' . ($hasAlpha ? 'yes' : 'no'));

                    /* create truecolor canvas & copy */
                    $tmp = imagecreatetruecolor($w, $h);
                    imagesavealpha($tmp, true);
                    $transCol = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
                    imagefill($tmp, 0, 0, $transCol);
                    imagecopy($tmp, $src, 0, 0, 0, 0, $w, $h);

                    /* reduce to palette */
                    imagetruecolortopalette($tmp, true, 256);
                    if ($hasAlpha) {
                        $idxTrans = imagecolorclosestalpha($tmp, 0, 0, 0, 127);
                        imagecolortransparent($tmp, $idxTrans);
                    }

                    ob_start();
                    imagegif($tmp);
                    $gifData = ob_get_clean();

                    imagedestroy($src);
                    imagedestroy($tmp);
                    log_debug('✅ GIF ready (' . strlen($gifData) . ' bytes)');

                    $base = $inputName ? pathinfo($inputName, PATHINFO_FILENAME)
                                       : pathinfo($path, PATHINFO_FILENAME);
                    $finalName = $base . '.gif';
                    $showPreview = true;
                }
            }

            /* saving */
            if ($gifData) {
                $dir = __DIR__ . '/z_files/emojis/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $dest = $dir . $finalName;
                $fileExists = file_exists($dest);

                if (isset($_POST['save'])) {
                    if ($fileExists && !isset($_POST['overwrite'])) {
                        log_debug('⚠️ File exists—confirm overwrite.');
                    } else {
                        file_put_contents($dest, $gifData);
                        log_debug('💾 Saved to /z_files/emojis/' . $finalName);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Image→GIF (transparent)</title>
<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: #f9f9fb;
  color: #333;
  padding: 30px;
  max-width: 700px;
  margin: auto;
}

h2, h3 {
  color: #2c3e50;
}

form {
  background: #fff;
  border: 1px solid #ddd;
  padding: 20px;
  border-radius: 6px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

label {
  display: block;
  margin-bottom: 18px;
  font-weight: 500;
}

input[type="text"] {
  width: 100%;
  padding: 12px 14px;
  font-size: 15px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-sizing: border-box;
  background-color: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

input[type="text"]:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
  outline: none;
}

button {
  padding: 10px 18px;
  margin-top: 10px;
  margin-right: 10px;
  font-size: 14px;
  border: none;
  border-radius: 4px;
  background-color: #3498db;
  color: #fff;
  cursor: pointer;
  transition: background-color 0.2s;
}

button:hover {
  background-color: #2980b9;
}

#debug {
  white-space: pre-wrap;
  background: #fcfcfc;
  padding: 12px;
  border: 1px solid #bbb;
  border-left: 4px solid #888;
  font-family: monospace;
  color: #444;
  margin-top: 20px;
  border-radius: 4px;
}

img {
  margin-top: 20px;
  max-width: 220px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
</style>
</head>
<body>

<h2>URL → Transparent GIF</h2>
<form method="post">
  <label>Image URL:<br>
    <input name="image_url" required value="<?=htmlspecialchars($_POST['image_url']??'')?>">
  </label><br>
  <label>Filename (optional):<br>
    <input name="filename" value="<?=htmlspecialchars($_POST['filename']??'')?>">
  </label><br><br>
  <button type="submit">Convert</button>

  <?php if($gifData && !isset($_POST['save'])): ?>
    <button name="save" value="1">Save to /z_files/emojis/</button>
  <?php endif; ?>

  <?php if(isset($_POST['save'],$fileExists) && $fileExists && !isset($_POST['overwrite'])): ?>
    <input type="hidden" name="overwrite" value="1">
    <button name="save" value="1">Yes, overwrite</button>
  <?php endif; ?>
</form>

<?php if($debug): ?>
<h3>Debug</h3><div id="debug"><?=htmlspecialchars(join("\n",$debug))?></div>
<?php endif; ?>

<?php if($showPreview && $gifData): ?>
<h3>Preview</h3>
<img src="data:image/gif;base64,<?=base64_encode($gifData)?>" alt="gif">
<?php endif; ?>

</body>
</html>
