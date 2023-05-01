<?php 

/**
* Title: Talking AI Script
* Author: L. Hogan <bentbot@outlook.com>
* Date Created: April 18, 2023
* Last Updated: April 27, 2023
* References: https://chat.openai.com/ https://cloud.google.com/text-to-speech
* Requirements: OpenAI API Key, Google TTS API Key, afplay / ffmpeg
**/

$openai_api_key = 'OPEN_API_KEY';
$google_api_key = "GOOGLE_API_KEY";

$mute = false;
$default_voice_language = 'en-us'; 		// TTS Language
$default_voice = 'en-US-Neural2-H'; 	// TTS Voice Name
$default_speaking_rate = 0.9; 			// TTS Speed
$default_pitch = 2; 					// TTS Tone
$default_volume = 0.60556; 				// TTS Volume
$default_file = 'ai_speaking';  		// MP3 Filename ['ai_speaking' may delete itself]
$trash_collect = true; 					// Remove old MP3 files generated by the WebUI.
$default_prompt = false; 				// A default chat prompt to send to OpenAI.
$show_indepth_ai = false; 				// Show the raw & more in-depth OpenAI result.
$show_json_return = false; 				// Show the raw OpenAI result in JSON format.

global $said;
global $filters;
function available_voices($rand,$default_voice,$default_pitch,$default_speaking_rate,$default_volume,$google_api_key) {
	$voices = [
		"en-US-Neural2-A",
		"en-US-Neural2-C",
		"en-US-Neural2-D",
		"en-US-Neural2-E",
		"en-US-Neural2-F",
		"en-US-Neural2-G",
		"en-US-Neural2-H",
		"en-US-Neural2-I",
		"en-US-Neural2-J",
		"en-US-Studio-M",
		"en-US-Studio-O",
		"en-US-Wavenet-G",
		"en-US-Wavenet-H",
		"en-US-Wavenet-I",
		"en-US-Wavenet-J",
		"en-US-Wavenet-A",
		"en-US-Wavenet-B",
		"en-US-Wavenet-C",
		"en-US-Wavenet-D",
		"en-US-Wavenet-E",
		"en-US-Wavenet-F",
		"en-US-News-K",
		"en-US-News-L",
		"en-US-News-M",
		"en-US-News-N",
		"en-US-Standard-A",
		"en-US-Standard-B",
		"en-US-Standard-C",
		"en-US-Standard-D",
		"en-US-Standard-E",
		"en-US-Standard-F",
		"en-US-Standard-G",
		"en-US-Standard-H",
		"en-US-Standard-I",
		"en-US-Standard-J"
	];
	if($rand) {
		$r=rand(0,count($voices)-1);
		return $voices[$r];
	} else {
		echo("\nThe available voices in this program are:\n\n");
		read((rand(0,1)===1?'Hi there.':'Hey!').' The available voices in this program are:','en-us',$default_voice,$default_pitch,$default_speaking_rate,$default_volume,'ai_speaking',$google_api_key,true);
		foreach ($voices as $key => $value) 
		foreach ($voices as $key => $value) {
			echo("  \t".$value."\n");
			read('Hello, I am AI.','en-us',$value,1,1,$default_volume,'ai_speaking',$google_api_key,true);
			sleep(1);
		}
		echo("\n");
	}
}
if(isset( $argc )) {
	$filters = array_fill(0, 3, null);
	for($i = 1; $i < $argc; $i++) {
		$filters[$i - 1] = $argv[$i];
	}
}
$prompt = (isset($filters[0]))?$filters[0]:(isset($_REQUEST['prompt'])?$_REQUEST['prompt']:$default_prompt);
$pitch = (isset($filters[1]))?$filters[1]:(isset($_REQUEST['pitch'])?$_REQUEST['pitch']:$default_pitch);
$speakingRate = (isset($filters[2]))?$filters[2]:(isset($_REQUEST['speakingRate'])?$_REQUEST['speakingRate']:$default_speaking_rate);
$volume = (isset($filters[3]))?$filters[3]:(isset($_REQUEST['volume'])?$_REQUEST['volume']:$default_volume);
$voice = (isset($filters[4]))?$filters[4]:(isset($_REQUEST['voice'])?$_REQUEST['voice']:$default_voice);
$file = (isset($filters[5]))?$filters[5]:(isset($_REQUEST['file'])?$_REQUEST['file']:$default_file);
$language = (isset($filters[6]))?$filters[6]:(isset($_REQUEST['language'])?$_REQUEST['language']:$default_voice_language);
$muting = ( $pitch === false || $speakingRate === false || $volume === false ) ? true : false;
if(!$prompt||$prompt==""||$prompt=="'-h'"||$prompt=="'--help'") {
	if(isset( $argc )) {
		echo("  Run the script with regular script notation. Example:\n");
		echo("   ai --voices [see all voices available]\n");
		echo("   ai [chat] [pitch] [rate] [vol] ['voice'] ['file_name'] ['voice_language']\n");
		echo("      [chat] 'Ask for something here' \n");
		echo("      [pitch] 0.9 / 1 / 1.3 / 2\n");
		echo("      [speakingRate] 0.8 / 1 / 1.2 / 2.3\n");
		echo("      [volume] 0.2 / 0.4 / 0.6 / 1\n");
		echo("      [voice] en-US-Studio-M\n");
		echo("      [filename] 'voice_file' [output the spoken audio to MP3]\n");
		echo("      [language] en-us\n");
		echo("  Examples:\n");
		echo("   ai what is the sum of pi\n");
		echo("   php Speaking AI.php --voices\n");
		echo("   php ./Speaking\ AI.php \"where's pluto\" 1 1 1 'en-US-Neural2-H';\n");
		echo("   ai --help\n");
	}
} else if ($prompt=="'--voices'") {
	available_voices(false,$default_voice,$default_pitch,$default_speaking_rate,$default_volume,$google_api_key); exit();
} else if ($voice=="'random'") {
	$voice = available_voices(true,$default_voice,$default_pitch,$default_speaking_rate,$default_volume,$google_api_key);
}

if( isset($prompt) && $prompt!=false ) post($prompt,$language,$voice,$pitch,$speakingRate,$volume,$file,$muting,$show_indepth_ai,$show_json_return,$openai_api_key,$google_api_key,$trash_collect);

function post($prompt,$language,$voice,$pitch,$speakingRate,$volume,$file,$muting,$show_indepth_ai,$show_json_return,$openai_api_key,$google_api_key,$trash_collect) {
	if(getenv("OPEN_AI_API_KEY")!==false) $openai_api_key=getenv('OPEN_AI_API_KEY');
	$url = 'https://api.openai.com/v1/chat/completions';
	$post_data = [
		"model"=>"gpt-3.5-turbo",
			"messages"=>[[
				"role"=> "user", 
				"content"=> $prompt
			]]
	];
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode($post_data),
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $openai_api_key,
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	if(isset($response)){
		$data = json_decode($response, true);
		if(isset($data)){
			if(isset($data['choices'][0]['message'])){
				$text = $data['choices'][0]['message']['content'];
				$read_lines=str_replace('- ', ' ... - ',$text);
				if($show_indepth_ai) {
					if($show_json_return) {
						print_r($data);
					} else {
						echo $response;
					}
				} else {
					echo("\n	".$text."\n\n");
				}
				if (!$muting) {
					read($read_lines,$language,$voice,$pitch,$speakingRate,$volume,$file,$google_api_key,$trash_collect);
				}
				$said = true;
				exit();
			} else {
				if($show_json_return) {
					print_r($data);
				} else {
					echo $response;
				}
				exit();
			}
		}
	}
}
function read($text,$language,$voice,$pitch,$speakingRate,$vol,$file,$google_api_key,$trash_collect) {
	if(getenv("GOOGLE_TTS_KEY")!==false) $google_api_key=getenv("GOOGLE_TTS_KEY");
	if($voice&&$voice!='0'&&$voice!='false'&&$file&&$file!='false'&&$file!='0') {
		$filename = str_replace(' ', '_', strtolower($file)).'.mp3';
		$myfile = fopen($filename, "w") or die("Unable to open file!");
		$post_data = [
			'input' => [
				'text' => $text
			],
			'voice' => [
				'languageCode' => $language,
				'name' => $voice
			],
			'audioConfig' => [
				'audioEncoding' => 'MP3',
				'pitch' => $pitch,
				'speakingRate' => $speakingRate,
			]
		];
		$url = "https://texttospeech.googleapis.com/v1/text:synthesize";
		$post = json_encode($post_data);
		$google_api_ch = curl_init();
		curl_setopt($google_api_ch, CURLOPT_URL, $url);
		curl_setopt($google_api_ch, CURLOPT_HTTPHEADER, [
			'X-Goog-Api-Key: '.$google_api_key,
			'Content-Type: application/json; charset=utf-8'
		]);
		curl_setopt($google_api_ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($google_api_ch, CURLOPT_POST,1);
		curl_setopt($google_api_ch, CURLOPT_POSTFIELDS, $post);
		$result=curl_exec( $google_api_ch );
		curl_close( $google_api_ch );
		$content = json_decode($result);
		if(isset($content->error)) {
			if($content->error->message!='This request contains sentences that are too long.')
				if($vol>0)echo($content->error->message);
		} else if($file&&$file!='false'&&$file!='0') {
			$base64 = $content->audioContent;
			$mp3 = base64_decode($base64);
			fwrite($myfile, $mp3);
			fclose($myfile);
			chmod($filename, 0755);
			if($vol>0)play($filename,$vol);
			if($trash_collect&&$file=='ai_speaking')unlink($filename);
		}
	}
}
function play($filename,$volumes) {
	if(isset($volumes)) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$cmd="ffplay -v 0 -volume ".$volumes." -nodisp -autoexit ".$filename;
		} else {
			$cmd="afplay -v ".$volumes." ./".$filename.";";
		}
	} else {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$cmd="ffplay -v 0 -volume 1 -nodisp -autoexit ".$filename;
		} else {
			$cmd="afplay -v 1 ./".$filename.";";	
		}
	}
	exec($cmd);
}
if(!$said) {
	if($trash_collect){
		$current_time = time();
		foreach(glob("./*.mp3") as $file) {
			$file_creation_time = filemtime($file);
			$time_diff = $current_time - $file_creation_time;
			if ($time_diff >= 86400) unlink($file);
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
	<style type="text/css">
		body,html {
			margin: 0;
		}
		.readout {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 28%;
			padding: 0 2% 0% 2%;
		}
		.userinput {
			padding: 5% 1%;
			position: absolute;
			bottom:0;
			left:0;
			right:0;
			height:20%;
			overflow: hidden;
		}
		textarea {
			width: 83%;
			height: 97%;
			padding: 1.5%;
			font-family: 'Helvetica', system-ui;
			font-size: 18px;
			font-weight: 300;
			line-height: 28px;
			border: 5px solid #dadada;
			border-radius: 16px;
			background: #f4f4f4;
		}
		.ask_button {
			border-radius: 8px;
			height: auto;
			padding: 29% 0;
			background: #1f57d7;
			float: right;
			width: 100%;
			color: white;
			text-align: center;
			font-family: 'Helvetica', system-ui;
			font-weight: 600;
			text-shadow: 1px 1px 1px BLACK;
			cursor: pointer;
    		border: 2px solid #dadada; 
		}
		.ask_button:hover {
			background: #0b70eb;
    	}
		.ask_button:active {
			background: #0b70eb;
    		border: 2px solid #a1bbff; 
    	}
		.mute_button,.save_button {
    		border: 2px solid #dadada; 
			border-radius: 8px;
			height: auto;
			padding: 9% 0;
			background: #ededed;
			float: right;
			width: 100%;
			color: #212121;
			text-align: center;
			font-family: 'Helvetica', system-ui;
			font-weight: 300;
			cursor: pointer;
			margin-top: 3%;
		}
		.mute_button:hover {
			background: #E3F2FD;
			border-color: #bbcad7;
		}
		.save_button:hover {
			background: #fcfdef;
			border: 2px solid #CDDC39;
		}
		.mute_button:active {
			background: #E3F2FD;
			border-color: #bbcad7;
		}
		.save_button:active {
			background: #fcfdef;
			border: 2px solid #CDDC39;
		}
		.btns {
		    width: 10.2%;
		    float: right;
		    height: 100%;
		    margin-right: 1%;
		}
		li.answer {
			font-family: 'Helvetica';
			padding: 26px 14px;
			border-bottom: 2px dashed #e4d8c9;
			list-style: none;
			line-height: 21.9px;
			color: #272727;
		}
		.messages {
			height: 100%;
			overflow: auto;
			margin: 0;
			padding: 0;
		}
		i.Q {color: gray;}
		.ask_prompt {
			text-align: center;
			font-family: 'Arial', sans-serif;
			position: fixed;
			letter-spacing: 0.3px;
			left: 50%;
			top: 10%;
			transform: translate(-50%, 0px);
			font-size: 22px;
			opacity: 1;
			text-shadow: 1px 1px 1px #000000;
			color: #ffffff6b;
			z-index: 3;
    }
    .loader {
    	pointer-events: none;
    	display: none;
	left: 50%;
	top: 26.6%;
	transform: translate(-50%, 0px);
	position: fixed;
    }
    .a {
    	padding-left: 3.5%;
    }
    .w {
    	margin-left: 5px;
    }
    a.about {
    	color: #3e3e3e;
		font-size: 10px;
		font-family: 'Helvetica', sans-serif;
		text-align: center;
		width: 100%;
		display: inline-block;
    }
	</style>
	<script type="text/javascript">
		$( document ).ready(function() {
			var lastMessage, lastQuery, audio = $('#audio');
			$('.html_input').focus().keyup((e)=>{
				if(e.key=='Enter') {
					e.preventDefault();
					ask();
				}
			});
			var muted = false;
			$('.mute_button').html(muted?'Voice On':'Mute');
			$('.mute_button').click((e)=>{
				muted = muted?false:true;
				if(muted){
					audio[0].volume = 0;
				} else {
					audio[0].volume = <?php echo $volume; ?>;
				}
				$('.mute_button').html(muted?'Voice On':'Mute');
			})
			$('.save_button').click((e)=>{
				if(lastMessage){
					save(lastMessage,lastQuery);
				} else {
					ask();
				}
			});
			$('.ask_button').click((e)=>{ask();});
			var href = window.location.href;
			var dir = href.substring(0, href.lastIndexOf('/')) + "/";
			function ask(save) {
				var words = $('.html_input').val();
				setTimeout( () => { $('.loader').fadeIn(); }, 500 );
				if(!words||words=='')return;
				$('.ask_prompt').fadeOut();
				var rand = Math.floor(Math.random() * 999997);
				
				$('.html_input').val('').focus();
				$.ajax({
					method: "POST",
					url: href,
					data: { 
						prompt: words,
						file: 'web_'+rand
					}
				}).done(function( msg ) {
					if(save)save(msg,words);
					lastMessage=msg;lastQuery=words;
					msg=msg.replace(/\n/g, "<br />");
					$('.loader').fadeOut();
					$('.messages').append("<li class='answer'><i class='Q'>Query: </i>  <b class='w'>"+words+"</b><br /><div class='a'>"+msg+"</div></li>");
					$('#mp3Source').attr('src',dir+'web_'+rand+'.mp3');
					audio[0].volume = muted ? 0 : <?php echo $volume; ?>;
					audio[0].pause();
					audio[0].load();
					audio[0].oncanplaythrough = audio[0].play();
				});
			}
			function save(msg,file) {
				var link = document.createElement('a');
				link.href = 'data:text/plain;charset=UTF-8,' + escape(msg);
				link.download = file+'ai.txt';
				link.click();
			}
		});
	</script>
	<title>AI Search</title>
</head>
<body>
	<div class="ask_prompt">Ask the AI anything...</div>
	<img class="loader" src="loading.gif">
	<div class="readout">
		<ul class="messages"></ul>
	</div>
	<div class="userinput">
		<textarea placeholder="Place your search query here..." class="html_input" type="text" name="html_input"></textarea>
		<div class="btns">
			<div class="ask_button">Ask the AI</div>
			<div class="save_button">Save</div>
			<div class="mute_button">Mute</div>
			<a class="about" href="https://github.com/bentbot/Talking-AI" target="_blank">Learn About the AI</a>
		</div>
	</div>
	<audio id="audio">
		<source id="mp3Source" type="audio/mp3" />
	</audio>
</body>
</html>
<?php } ?>
