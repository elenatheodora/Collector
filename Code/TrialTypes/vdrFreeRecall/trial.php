<?php
	$compTime = 60;					// time in seconds to use for 'computer' timing
	trialTiming();					// determines timing and user/computer timing mode

	$prompt = explode('|', $currentTrial['Procedure']['Procedure Notes']);
?>
    <div class="prompt"><?= trim($prompt[0]) ?></div>
	<?php
		if (isset($prompt[1])) {
			$stimPrompt = trim($prompt[1]);
			$cues = explode('|', $cue);
			$answers = explode('|', $answer);
			foreach ($cues as $i => $thisCue) {
				echo str_replace(array('$cue', '$answer'), array($thisCue, $answers[$i]), $stimPrompt);
			}
		}
	?>
    <form class="<?php echo $formClass; ?> collector-form"  autocomplete="off"  action="<?php echo $postTo; ?>"  method="post">
        <textarea rows="20" cols="55" name="Response" class="precache" wrap="physical" value=""></textarea>
        <input class="hidden"  id="RT"     name="RT"       type="text" value="RT"       />
        <input class="hidden"  id="RTkey"  name="RTkey"    type="text" value="no press" />
        <input class="hidden"  id="RTlast" name="RTlast"   type="text" value="no press" />
        <div class="textleft">
            <input class="button button-trial-advance" id="FormSubmitButton" type="submit" value="Submit"   />
        </div>
    </form>