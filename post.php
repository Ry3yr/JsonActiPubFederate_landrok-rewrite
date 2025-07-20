<iframe src="https:.//alceawis.com/other/extra/scripts/fakesocialmedia/lastshorthands.html" id="iframe" width="600" height="100"></iframe> <br><hr>
    <script src="/other/extra/scripts/libraries/crypto-js.min.js"></script>
<script>
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) {
            return; // Ensure the message is from the same origin
        }
        if (event.data.type === 'shorthandSelected') {
            // Append the selected shorthand to the textarea
            const textbox = document.getElementById('textbox');
            textbox.value += event.data.shorthand + " ";
        }
    });
    document.getElementById('parentBtn').addEventListener('click', () => {
        const iframe = document.getElementById('iframe');
        iframe.contentWindow.postMessage({
            type: 'changeBackgroundColor',
            color: 'lightgreen'
        }, '*');
    });
</script>
    <script>
        function pasteCharacter() {
            const textbox = document.getElementById('textbox');
            const cursorPos = textbox.selectionStart;
            textbox.value = textbox.value.slice(0, cursorPos) + '𒐫' + textbox.value.slice(cursorPos);
            textbox.selectionStart = textbox.selectionEnd = cursorPos + 1;
        }
    </script>
    <button onclick="pasteCharacter()">QUOTE</button>
&nbsp;<button onclick="const url=document.getElementById('urlInput').value.trim();if(url && new RegExp('^(https?|ftp)://').test(url))document.getElementById('textbox').value='💬'+url+'💬 \n•acws #acws\n'+document.getElementById('textbox').value;else alert('Invalid Fediverse Post URL')">💬</button><input id="urlInput" style="border:none;width:250px;padding:5px" placeholder="Enter URL"/>
<br>
<a href="#" id="pasteLink">#CurrListeningAlcea</a>
<script>document.getElementById('pasteLink').addEventListener('click', e => { e.preventDefault(); const t = document.getElementById('textbox'), p = t.selectionStart; t.value = t.value.slice(0, p) + '#CurrListeningAlcea' + t.value.slice(p); t.selectionStart = t.selectionEnd = p + '#CurrListeningAlcea'.length; });</script>
<!-- [<a target="_blank" href="https://alceawis.com/updatejson.php" style=color:lightpink>updt</a>]-->

<br>
<?php
error_reporting(0);
?>
<script src="/jquery.min.js"></script>
<style>.hidden-image {display: none;}</style>
<input type="text" id="imageKeyword" placeholder="Enter image keyword">
<script>
document.addEventListener("DOMContentLoaded", function() {
// Get all the images on the page
var images = document.getElementsByTagName("img");
function handleKeywordChange() {
var keyword = document.getElementById('imageKeyword').value.toLowerCase();
for (var i = 0; i < images.length; i++) {
var img = images[i];
var imgUrl = img.getAttribute("src");
if (imgUrl && imgUrl.toLowerCase().includes(keyword)) {
img.classList.remove("hidden-image");
} else {
img.classList.add("hidden-image");}}}
document.getElementById('imageKeyword').addEventListener('input', handleKeywordChange);
handleKeywordChange();
});
</script><hr>
<!----FetchEmoji--->
<?php
if (isset($_POST['button_pressed'])) {
    $url = "https://alcea-wisteria.de/z_files/emoji/";
    $html = file_get_contents($url);
    preg_match_all('/<a href=[\'"](.*?\.gif)[\'"]/i', $html, $matches);
    $gifUrls = $matches[1];
    $gridSize = 35; // Number of columns in the grid
    $count = 0;
    echo "<table>";
    echo "<tr>";
    foreach ($gifUrls as $gifUrl) {
        $filename = basename($gifUrl);
        echo '<td><a href="javascript:void(0);" onclick="insertEmoji(\'' . $filename . '\');"><img src="https://alcea-wisteria.de/z_files/emoji/' . $filename . '" width=30></a></td>';
        $count++;
        if ($count % $gridSize == 0) {
            echo "</tr><tr>";
        }
    }
    echo "</tr>";
    echo "</table>";
}
?>
<form method="post">
    <input type="submit" name="button_pressed" value="LoadEmoji">
</form>
<script>
function insertEmoji(emoji) {
    var textarea = document.getElementById('textbox');
    textarea.value += ':' + emoji.split('.')[0] + ': ';
}
</script>
<button id="writeBtn" style="background-color: white; border: 1px solid black; padding: 1px 20px; font-size: 16px; cursor: pointer;">💾WRITE</button>
<button id="readBtn" style="background-color: white; border: 1px solid black; padding: 1px 20px; font-size: 16px; cursor: pointer;">📥READ</button>


<!--<a href="#" id="loadDataLink">Load</a>
<input type="number" id="entryNumber" min="1" value="1" style="width: 40px; margin-left: 5px;">|
<a href="#" id="submitEditLink">Submit Edit</a>
 <a target="_blank" href="https://mas.to/search?q=%40alceawis%40alceawis.com&type=accounts" style=color:lightpink>check</a>
<textarea id="originalTextbox" style="display:none;"></textarea>
<script>
function loadEntryByReversedNumber(entryNum) {
    fetch('/other/extra/scripts/fakesocialmedia/data_alcea.json', { cache: 'no-cache' })
      .then(response => response.json())
      .then(data => {
        const reversedIndex = data.length - entryNum;

        if (reversedIndex < 0 || reversedIndex >= data.length) {
          alert('Entry number exceeds data length');
          return;
        }

        const entry = data[reversedIndex];
        const innerKey = Object.keys(entry)[0];
        const value = entry[innerKey].value;

        document.getElementById('textbox').value = value;
        document.getElementById('originalTextbox').value = value;
      })
      .catch(error => {
        console.error('Error loading data:', error);
      });
  }

  // Handle Load click
  document.getElementById('loadDataLink').addEventListener('click', function(e) {
    e.preventDefault();
    const userEntry = parseInt(document.getElementById('entryNumber').value, 10);

    if (userEntry < 1) {
      alert('Please enter a number 1 or greater');
      return;
    }

    loadEntryByReversedNumber(userEntry);
  });

  // Handle Submit Edit
  document.getElementById('submitEditLink').addEventListener('click', function(e) {
    e.preventDefault();
    const newText = document.getElementById('textbox').value;
    const originalText = document.getElementById('originalTextbox').value;
    const params = new URLSearchParams({
      new: newText,
      original: originalText
    });
    window.location.href = 'edit.php?' + params.toString();
  });

  // Prefill entryNumber with the last entry number on page load (reversed numbering)
  window.addEventListener('DOMContentLoaded', function() {
    fetch('/other/extra/scripts/fakesocialmedia/data_alcea.json', { cache: 'no-cache' })
      .then(response => response.json())
      .then(data => {
        document.getElementById('entryNumber').value = data.length;
      })
      .catch(error => {
        console.error('Error preloading entry number:', error);
      });
  });
</script>-->








<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["specificButton"])) {
    $value = $_POST["textbox"];
    $user = $_GET["user"];
    $date = date("Ymd");
    $newData = array($date => array("value" => $value));
    $hashtags = array();
    preg_match_all('/#(\w+)/', $value, $matches);
    if (!empty($matches[1])) {
        $hashtags = $matches[1];
    }
    if (!empty($hashtags)) {
        $newData[$date]["hashtags"] = implode(", ", $hashtags);
    }

    $fullFilename = "data_" . $user . ".json";
    $partFilename = "data_" . $user . ".json";

    // Save all data to data_$user.json
    if (file_exists($fullFilename)) {
        $existingData = json_decode(file_get_contents($fullFilename), true);
    } else {
        $existingData = array();
    }
    array_unshift($existingData, $newData);
    file_put_contents($fullFilename, json_encode($existingData, JSON_PRETTY_PRINT));

    // Overwrite data_part_$user.json with the latest 60 entries
    $partialData = array($newData);
    if (file_exists($fullFilename)) {
        $fullData = json_decode(file_get_contents($fullFilename), true);
        $partialData = array_slice($fullData, 0, 60);
    }
    //file_put_contents($partFilename, json_encode($partialData, JSON_PRETTY_PRINT));
}
?>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"] . "?user=" . $_GET["user"]; ?>">
    <textarea id="textbox" name="textbox" rows="4" cols="50"></textarea>
    <input type="submit" name="specificButton" value="Submit">
</form>



<!----Text-Clip--handler--->
    <script> document.getElementById('writeBtn').addEventListener('click', function () {
            const text = document.getElementById('textbox').value;
            fetch('clip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'send=' + encodeURIComponent(text)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Write failed');
                }
                console.log('Text saved successfully');
            })
            .catch(error => console.error(error));
        }); document.getElementById('readBtn').addEventListener('click', function () {
            fetch('clip.txt', { cache: 'no-cache' })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('textbox').value = data;
                });
        });
    </script>


<!--------------Info----------->
    <br>
      <!-- <button type="submit" name="specificButton">save</button>-->
</form>
<!--<a target="_blank" href="post2mtd.html" style=color:blue>Post2Mtd</a> <a target="_blank" href="https://alcea-wisteria.de/PHP//0demo/2023-08-15-JSFiddle-Clone/htmls/2023-11-04-Advanced-mtd-tl-renderer-w-Query-string-support.html?instance=mas.to&userid=111958546062297646" style=color:blue>TimeLine</a>
<a target="_blank" href="https://mas.to/@arusea.rss" style=color:blue>RSS</a> [<a target="_blank" href="https://alceawis.de/other/extra/scripts/fakesocialmedia/post.php?user=alcea&mode=lowbandwidth" style=color:blue>Lowbandwidth</a>]<a target="_blank" href="del.php?user=alcea" style=color:blue>del</a>-->
<?php
$user = $_GET['user'];
$uniqueId = uniqid();
$iframeSrc = "data_{$user}.json?v={$uniqueId}";
echo '<iframe src="' . $iframeSrc . '" style="border:0px #ffffff none;" name="statusit" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" height="250px" width="800" allowfullscreen></iframe>';
?>
<br><iframe src="
/other/extra/scripts/fakesocialmedia/check.php
" style="border:0px #ffffff none;" name="statusit" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" height="25px" width="100%" allowfullscreen></iframe>
<!--<iframe src="
/fakesocialrender_limited.html?user=alcea
" style="border:0px #ffffff none;" name="statusit" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" height="6000px" width="100%" allowfullscreen></iframe>-->




<!--
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
var baseUrl = "/fakesocialrender_limited.html";
$("#mtdcomm").load(baseUrl + "");
});
</script>
<div class="formClass">
<div id="mtdcomm">
</div>
</div>-->


