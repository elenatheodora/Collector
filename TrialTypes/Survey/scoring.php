<?php
    $data = array();
    
    # SAVE ALL DATA
    foreach ($_POST as $inpName => $resp) {
        if (!is_array($resp)) {
            $data[$inpName] = $resp;
        } else {
            $data[$inpName] = implode('|', $resp);
            foreach ($resp as $i => $iResp) {
                $cleanName = $inpName . '_' . ($i+1);
                if (!isset($data[$cleanName])) $data[$cleanName] = $iResp;
            }
        }
    }
    
    # FIND SURVEY, make sure we have the shuffled version that was actually used
    if (!isset($_SESSION['CurrentSurvey'])) {
        $err = "Error: current survey not saved into SESSION";
        trigger_error($err, E_USER_ERROR);
    }
    $survey = $_SESSION['CurrentSurvey'];
    
    # CUSTOM TYPE SCORING, let each survey type have access to its data
    if (is_numeric($item)) {
        $surveyFile = $cue;
    } else {
        $surveyFile = $item;
    }
    $trialTypeDir = dirname($trialFiles['display']);
    $surveyDir    = $_PATH->get('Common') . '/Surveys';
    require "$trialTypeDir/SurveyFunctions.php";
    
    $allSurveyTypes = getSurveyTypes($trialTypeDir);
    $customData     = array();
    
    $surveyIndex = 0;
    while (isset($survey[$surveyIndex])) {
        $type = $survey[$surveyIndex]['Type'];
        $type = cleanSurveyType($type);
        $surveyRows = array($survey[$surveyIndex]);
        ++$surveyIndex;
        
        while (
            isset($survey[$surveyIndex])
         && cleanSurveyType($survey[$surveyIndex]['Type']) === $type
        ) {
            $surveyRows[] = $survey[$surveyIndex];
            ++$surveyIndex;
        }
        
        if ($type === 'page_break') {
            continue;
        } elseif ($type === 'type_break') {
            continue;
        } elseif (isset($allSurveyTypes[$type]['scoring'])) {
            require $allSurveyTypes[$type]['scoring'];
        }
    }
    
    # SURVEY SCORING
    // required columns: Answers, Values
    if (isset($survey[0]['Answers'], $survey[0]['Values'])) {
        $scoreCols = array();
        foreach (array_keys($survey[0]) as $surveyCol) {
            if (strtolower(substr($surveyCol, 0, 5)) === 'score') {
                $scoreColumnParts = explode(':', $surveyCol);
                if (!isset($scoreColumnParts[1])) continue; // abandon ship
                $scoreName = trim($scoreColumnParts[1]);
                $scoreType = substr($scoreColumnParts[0], 5);
                $scoreType = strtolower(trim($scoreType));
                if ($scoreType !== 'average') $scoreType = 'sum';
                $scoreCols[$surveyCol] = array (
                    'Name' => $scoreName,
                    'Type' => $scoreType
                );
            }
        }

        foreach ($scoreCols as $col => $score) {
            $scoreName  = $score['Name'];
            $scoreType  = $score['Type'];
            $respValues = array();
            
            foreach ($survey as $surveyRow) {
                if ($surveyRow[$col] === '') continue; // this row not used for this scale
                $qName = $surveyRow['Question Name'];
                if (!isset($data[$qName])) continue; // somehow, this question isn't in the data
                $resp = $data[$qName];
                $answers = rangeToArray($surveyRow['Answers']);
                $values  = rangeToArray($surveyRow['Values']);
                foreach ($values as $val) {
                    if (!is_numeric($val)) continue 2; // cant use this row, values arent numeric
                }
                if (count($answers) !== count($values)) continue; // cant convert answer to value directly
                $answerValues = array_combine($answers, $values);
                if (!isset($answerValues[$resp])) continue; // this response isn't one of the listed answers
                
                // by this point, we should be good to go. the response exists, matches an answer, and the answer has a value
                if ($surveyRow[$col][0] === 'r' || $surveyRow[$col][0] === 'R') {
                    // reverse score this item
                    $answerIndices   = array_flip($answers);
                    $answerIndex     = $answerIndices[$resp];
                    $reversedAnswers = array_reverse($answers);
                    $reverseResp     = $reversedAnswers[$answerIndex];
                    $respFactor      = substr($surveyRow[$col], 1);
                    $respFactor      = ($respFactor === false) ? 1.0 : $respFactor; // can happen if the column entry is just 'r'
                    $respValue       = $answerValues[$reverseResp];
                } else {
                    $respFactor      = $surveyRow[$col];
                    $respValue       = $answerValues[$resp];
                }
                
                if (!is_numeric($respFactor)) continue; // a factor of 'string' means nothing
                
                // question: if the factor is 0, should this row be skipped? For now, its left in, but it will affect averages
                $respFactor   = (float) $respFactor;
                $respValues[] = $respValue * $respFactor;
            }
            
            if (count($respValues === 0)) $data["Score_$scoreName"] = 'no data'; // no data found for this scale
            
            // if you want to add more scoring types, put the code here, as an elseif
            if ($score['Type'] === 'average') {
                $computedScore = array_sum($respValues) / count($respValues);
            } else {
                $computedScore = array_sum($respValues);
            }
            
            $data["Score_$scoreName"] = $computedScore;
        }
    }
    
    unset($_SESSION['CurrentSurvey']);