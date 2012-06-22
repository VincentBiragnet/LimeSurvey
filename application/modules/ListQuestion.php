<?php
class ListQuestion extends QuestionModule
{
    public function getAnswerHTML()
    {
        global $dropdownthreshold;
        global $thissurvey;
        $clang=Yii::app()->lang;
        if ($thissurvey['nokeyboard']=='Y')
        {
            includeKeypad();
            $kpclass = "text-keypad";
        }
        else
        {
            $kpclass = "";
        }

        $checkconditionFunction = "checkconditions";

        $aQuestionAttributes = $this->getAttributeValues();

        //question attribute random order set?
        if ($aQuestionAttributes['random_order']==1) {
            $ansquery = "SELECT * FROM {{answers}} WHERE qid=$this->id AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' and scale_id=0 ORDER BY ".dbRandom();
        }

        //question attribute alphasort set?
        elseif ($aQuestionAttributes['alphasort']==1)
        {
            $ansquery = "SELECT * FROM {{answers}} WHERE qid=$this->id AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' and scale_id=0 ORDER BY answer";
        }

        //no question attributes -> order by sortorder
        else
        {
            $ansquery = "SELECT * FROM {{answers}} WHERE qid=$this->id AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' and scale_id=0 ORDER BY sortorder, answer";
        }

        $ansresult = dbExecuteAssoc($ansquery)->readAll();  //Checked
        $anscount = count($ansresult);

        if (trim($aQuestionAttributes['display_columns'])!='') {
            $dcols = $aQuestionAttributes['display_columns'];
        }
        else
        {
            $dcols= 1;
        }

        if (trim($aQuestionAttributes['other_replace_text'][$_SESSION['survey_'.$this->surveyid]['s_lang']])!='')
        {
            $othertext=$aQuestionAttributes['other_replace_text'][$_SESSION['survey_'.$this->surveyid]['s_lang']];
        }
        else
        {
            $othertext=$clang->gT('Other:');
        }

        if ($this->getOther()=='Y') {$anscount++;} //Count up for the Other answer
        if ($this->mandatory != 'Y' && SHOW_NO_ANSWER == 1) {$anscount++;} //Count up if "No answer" is showing

        $wrapper = setupColumns($dcols , $anscount,"answers-list radio-list","answer-item radio-item");
        $answer = $wrapper['whole-start'];
        
        //Time Limit Code
        if (trim($aQuestionAttributes['time_limit'])!='')
        {
            $answer .= return_timer_script($aQuestionAttributes, $ia);
        }
        //End Time Limit Code

        // Get array_filter stuff

        $rowcounter = 0;
        $colcounter = 1;
        $trbc='';

        foreach ($ansresult as $ansrow)
        {
            $myfname = $this->fieldname.$ansrow['code'];
            $check_ans = '';
            if ($_SESSION['survey_'.$this->surveyid][$this->fieldname] == $ansrow['code'])
            {
                $check_ans = CHECKED;
            }

            list($htmltbody2, $hiddenfield)=return_array_filter_strings($this, $aQuestionAttributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname, "li","answer-item radio-item");
            if(substr($wrapper['item-start'],0,4) == "\t<li")
            {
                $startitem = "\t$htmltbody2\n";
            } else {
                $startitem = $wrapper['item-start'];
            }

            $answer .= $startitem;
            $answer .= "\t$hiddenfield\n";
            $answer .='		<input class="radio" type="radio" value="'.$ansrow['code'].'" name="'.$this->fieldname.'" id="answer'.$this->fieldname.$ansrow['code'].'"'.$check_ans.' onclick="if (document.getElementById(\'answer'.$this->fieldname.'othertext\') != null) document.getElementById(\'answer'.$this->fieldname.'othertext\').value=\'\';'.$checkconditionFunction.'(this.value, this.name, this.type)" />
            <label for="answer'.$this->fieldname.$ansrow['code'].'" class="answertext">'.$ansrow['answer'].'</label>
            '.$wrapper['item-end'];

            ++$rowcounter;
            if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
            {
                if($colcounter == $wrapper['cols'] - 1)
                {
                    $answer .= $wrapper['col-devide-last'];
                }
                else
                {
                    $answer .= $wrapper['col-devide'];
                }
                $rowcounter = 0;
                ++$colcounter;
            }
        }

        if (isset($other) && $other=='Y')
        {

            $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
            $sSeperator = $sSeperator['seperator'];

            if ($aQuestionAttributes['other_numbers_only']==1)
            {
                $numbersonly = 'onkeypress="return goodchars(event,\'-0123456789'.$sSeperator.'\')"';
                $oth_checkconditionFunction = 'fixnum_checkconditions';
            }
            else
            {
                $numbersonly = '';
                $oth_checkconditionFunction = 'checkconditions';
            }


            if ($_SESSION['survey_'.$this->surveyid][$this->fieldname] == '-oth-')
            {
                $check_ans = CHECKED;
            }
            else
            {
                $check_ans = '';
            }

            $thisfieldname=$this->fieldname.'other';
            if (isset($_SESSION['survey_'.$this->surveyid][$thisfieldname]))
            {
                $dispVal = $_SESSION['survey_'.$this->surveyid][$thisfieldname];
                if ($aQuestionAttributes['other_numbers_only']==1)
                {
                    $dispVal = str_replace('.',$sSeperator,$dispVal);
                }
                $answer_other = ' value="'.htmlspecialchars($dispVal,ENT_QUOTES).'"';
            }
            else
            {
                $answer_other = ' value=""';
            }

            list($htmltbody2, $hiddenfield)=return_array_filter_strings($this, $aQuestionAttributes, $thissurvey, array("code"=>"other"), $thisfieldname, $trbc, $myfname, "li", "answer-item radio-item other-item other");

            if(substr($wrapper['item-start-other'],0,4) == "\t<li")
            {
                $startitem = "\t$htmltbody2\n";
            } else {
                $startitem = $wrapper['item-start-other'];
            }
            $answer .= $startitem;
            $answer .= "\t$hiddenfield\n";
            $answer .= '		<input class="radio" type="radio" value="-oth-" name="'.$this->fieldname.'" id="SOTH'.$this->fieldname.'"'.$check_ans.' onclick="'.$checkconditionFunction.'(this.value, this.name, this.type)" />
            <label for="SOTH'.$this->fieldname.'" class="answertext">'.$othertext.'</label>
            <label for="answer'.$this->fieldname.'othertext">
            <input type="text" class="text '.$kpclass.'" id="answer'.$this->fieldname.'othertext" name="'.$this->fieldname.'other" title="'.$clang->gT('Other').'"'.$answer_other.' '.$numbersonly.' onchange="if($.trim($(this).val())!=\'\'){ $(\'#SOTH'.$this->fieldname.'\').attr(\'checked\',\'checked\'); }; '.$oth_checkconditionFunction.'(this.value, this.name, this.type);" />
            </label>
            '.$wrapper['item-end'];

            ++$rowcounter;
            if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
            {
                if($colcounter == $wrapper['cols'] - 1)
                {
                    $answer .= $wrapper['col-devide-last'];
                }
                else
                {
                    $answer .= $wrapper['col-devide'];
                }
                $rowcounter = 0;
                ++$colcounter;
            }
        }

        if ($this->mandatory != 'Y' && SHOW_NO_ANSWER == 1)
        {
            if ((!$_SESSION['survey_'.$this->surveyid][$this->fieldname] || $_SESSION['survey_'.$this->surveyid][$this->fieldname] == '') || ($_SESSION['survey_'.$this->surveyid][$this->fieldname] == ' ' ))
            {
                $check_ans = CHECKED; //Check the "no answer" radio button if there is no answer in session.
            }
            else
            {
                $check_ans = '';
            }

            $answer .= $wrapper['item-start-noanswer'].'		<input class="radio" type="radio" name="'.$this->fieldname.'" id="answer'.$this->fieldname.'NANS" value=""'.$check_ans.' onclick="if (document.getElementById(\'answer'.$this->fieldname.'othertext\') != null) document.getElementById(\'answer'.$this->fieldname.'othertext\').value=\'\';'.$checkconditionFunction.'(this.value, this.name, this.type)" />
            <label for="answer'.$this->fieldname.'NANS" class="answertext">'.$clang->gT('No answer').'</label>
            '.$wrapper['item-end'];
            // --> END NEW FEATURE - SAVE

            ++$rowcounter;
            if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
            {
                if($colcounter == $wrapper['cols'] - 1)
                {
                    $answer .= $wrapper['col-devide-last'];
                }
                else
                {
                    $answer .= $wrapper['col-devide'];
                }
                $rowcounter = 0;
                ++$colcounter;
            }

        }
        //END OF ITEMS
        $answer .= $wrapper['whole-end'].'
        <input type="hidden" name="java'.$this->fieldname.'" id="java'.$this->fieldname."\" value=\"".$_SESSION['survey_'.$this->surveyid][$this->fieldname]."\" />\n";

        return $answer;
    }
    
    protected function getOther()
    {
        if ($this->other) return $this->other;
        $query = "SELECT other FROM {{questions}} WHERE qid=".$this->id." AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' ";
        $result = Yii::app()->db->createCommand($query)->query()->readAll();
        return $this->other = $result[count($result)-1];  //Checked
    }

    public function getTitle()
    {
        $clang=Yii::app()->lang;
        $aQuestionAttributes=$this->getAttributeValues();
        if ($aQuestionAttributes['hide_tip']==0)
        {
            return $this->text . "<br />\n<span class=\"questionhelp\">".$clang->gT('Choose one of the following answers').'</span>';
        }
        
        return $this->text;
    }
    
    public function getHelp()
    {
        $clang=Yii::app()->lang;
        $aQuestionAttributes=$this->getAttributeValues();
        if ($aQuestionAttributes['hide_tip']==0)
        {
            return $clang->gT('Choose one of the following answers');
        }
        
        return '';
    }
    
    public function createFieldmap($type=null)
    {
        $clang = Yii::app()->lang;
        $map = parent::createFieldmap($type);
        if($this->other=='Y')
        {
            $other = $map[$this->fieldname];
            $other['fieldname'].='other';
            $other['aid']='other';
            $other['subquestion']=$clang->gT("Other");
            if (isset($this->default) && isset($this->default['other'])) $other['defaultvalue']=$this->default['other'];
            else unset($other['defaultvalue']);
            $q = clone $this;
            $q->fieldname .= 'other';
            $q->aid = 'other';
            $q->default = isset($other['defaultvalues'])?$other['defaultvalues']:null;
            $other['q']=$q;
            $other['pq']=$this;
            $map[$other['fieldname']]=$other;
        }
        return $map;
    }
        
    public function getExtendedAnswer($value, $language)
    {
        if ($value == "-oth-")
        {
            return $language->gT("Other")." [$value]";
        }
        $result = Answers::model()->getAnswerFromCode($this->id,$value,$language->langcode) or die ("Couldn't get answer."); //Checked
        if($result->count())
        {
            $result =array_values($result->readAll());
            return $result[count($result)-1]." [$value]";
        }
        return $value;
    }
    
    public function getQuotaValue($value)
    {
        return array($this->surveyid.'X'.$this->gid.'X'.$this->id => $value);
    }
    
    public function availableAttributes($attr = false)
    {
        $attrs=array("alphasort","array_filter","array_filter_exclude","array_filter_style","display_columns","statistics_showgraph","statistics_graphtype","hide_tip","hidden","other_comment_mandatory","other_numbers_only","other_replace_text","page_break","public_statistics","random_order","parent_order","scale_export","random_group","time_limit","time_limit_action","time_limit_disable_next","time_limit_disable_prev","time_limit_countdown_message","time_limit_timer_style","time_limit_message_delay","time_limit_message","time_limit_message_style","time_limit_warning","time_limit_warning_display_time","time_limit_warning_message","time_limit_warning_style","time_limit_warning_2","time_limit_warning_2_display_time","time_limit_warning_2_message","time_limit_warning_2_style");
        return $attr?array_key_exists($attr,$attrs):$attrs;
    }

    public function questionProperties($prop = false)
    {
        $clang=Yii::app()->lang;
        $props=array('description' => $clang->gT("List (Radio)"),'group' => $clang->gT("Single choice questions"),'subquestions' => 0,'class' => 'list-radio','hasdefaultvalues' => 1,'assessable' => 1,'answerscales' => 1);
        return $prop?$props[$prop]:$props;
    }
}
?>