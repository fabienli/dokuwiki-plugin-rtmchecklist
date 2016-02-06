<?php
/**
 * rtmchecklist plugin, mail a tasks list to RTM
 * @author     Fabien Lisiecki
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

if(!defined('RTMCHECKLIST_ROOTDIR')) define('RTMCHECKLIST_ROOTDIR', DOKU_PLUGIN."rtmchecklist/");

class action_plugin_rtmchecklist extends DokuWiki_Action_Plugin {

  /**
   * return some info
   */
  function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
  }

  /**
   * Register its handlers with the DokuWiki's event controller
   */
  function register(Doku_Event_Handler $controller) {
    $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handleTplActUnknown', array());
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleActPreprocess', array());
    $controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'handle_profile_form', array());
  }

    /*
     * Add a field for RTM import email
     */
    function handle_profile_form(&$event, $param){
            global $INFO;
            
            if (empty($_SERVER['REMOTE_USER'])) {
                return; // Not logged in
            }
            // include conf file for user
            $username = $_SERVER['REMOTE_USER'];
            $profileFilename = RTMCHECKLIST_ROOTDIR.'conf/profile_'.$username.'.php';
            if (is_writable($profileFilename)) {
                include($profileFilename);
            }
            $currentRtmEmail = $INFO['userinfo']['rtmemail'];

            $inputRtmEmail = '<label class="block"><span>'.$this->getLang('rtmemail').'</span> ';
            $inputRtmEmail.= '<input type="text" name="rtmemail" class="edit" size="200" value="'.$currentRtmEmail.'"/>';
            $inputRtmEmail.= '</label><br />';

            $rtm_form = '<form id="dw__rtmemailregister" method="post" action="" accept-charset="utf-8"><div class="no">';
            $rtm_form = '<input type="hidden" name="sectok" value="'.getSecurityToken().'" />';
            $rtm_form.= '<fieldset ><legend>'.$this->getLang('legend_title').'</legend>';
            $rtm_form.= '<input type="hidden" name="do" value="rtmemailregister" />';
            $rtm_form.= $inputRtmEmail;
            $rtm_form.= '<input type="submit" value="Save" class="button" />';
            $rtm_form.= '<input type="reset" value="Reset" class="button" />';
            $rtm_form.= '</fieldset>';
            $rtm_form.= '</div></form>';
            $pos = $event->data->findElementByAttribute('type', 'reset');
            $event->data->insertElement($pos+3, $rtm_form);
    }
    
    
  function handleActPreprocess(&$event, $param){
    if($event->data == 'plugin_rtmsend'){
        // Accept the action
        $event->preventDefault();
        $event->stopPropagation();
        return; 
    }
    else if($event->data == 'rtmemailregister') {
        // Accept the action
        $event->preventDefault();
        $event->stopPropagation();
        return; 
    }
    else {
        // nothing to do for us
        return;
    }
    return;
  }

    /**
     * Hook for event TPL_ACT_UNKNOWN, action 
     */
    function handleTplActUnknown(&$event, $param) {
        if ($event->data == 'plugin_rtmsend') {
            global $INPUT; //available since release 2012-10-13 "Adora Belle"
            global $INFO;
            
            $wikitext = "=== ".$this->getLang('Results')." ===\n";
            if(!checksecuritytoken()) {
                return;
            }
            // send the checklist to RTM
            $sendResult = false;
            if (empty($_SERVER['REMOTE_USER'])) {
                $event->data = 'login';
                return; // Not logged in
            }
            // include conf file for user
            $username = $_SERVER['REMOTE_USER'];
            $profileFilename = RTMCHECKLIST_ROOTDIR.'conf/profile_'.$username.'.php';
            if (is_writable($profileFilename)) {
                include($profileFilename);
            }
            //
            $tasksList = $INPUT->str('taskslist');
            $rtmEmail  = $INFO['userinfo']['rtmemail'];
            if(empty($rtmEmail)) {
                $wikitext .= $this->getLang('error_fill_email');
            }
            // send the mail
            if(mail_send($rtmEmail,
                         "", // no title
                         $tasksList)) { 
                $sendResult = true;
            }
            
            // Accept the action
            $event->preventDefault();
            $event->stopPropagation();
            
            // show final result
            if($sendResult)
                $wikitext.= $this->getLang('checklist_sent');
            else
                $wikitext.= $this->getLang('checklist_not_sent');
            $pageId = $INPUT->str('id');
            $wikitext.= "[[".htmlspecialchars($pageId)."|".$this->getLang('go_back_to').htmlspecialchars($pageId)." ".$this->getLang('page')."]]";
            // parse and render. TODO: cache
            $ret = p_render('xhtml',p_get_instructions($wikitext),$info);
            echo $ret;
        }
        else if($event->data == 'rtmemailregister') {
            global $INPUT; //available since release 2012-10-13 "Adora Belle"
            
            if(!checksecuritytoken()) {
                return;
            }
            // better safe than sorry
            if (empty($_SERVER['REMOTE_USER'])) {
                $event->data = 'login';
                return; // Not logged in
            }
            $username = $_SERVER['REMOTE_USER'];
            // make sure dir RTMCHECKLIST_ROOTDIR is created
            mkdir(RTMCHECKLIST_ROOTDIR.'conf', 0700);
            //write file: RTMCHECKLIST_ROOTDIR.'conf/profile_'.$username.'.php'
            $fp = fopen(RTMCHECKLIST_ROOTDIR.'conf/profile_'.$username.'.php', 'w');
            fwrite($fp, "<?php\n");
            fwrite($fp, "global \$INFO;\n\n");
            fwrite($fp, "\$INFO['userinfo']['rtmemail'] = '".htmlspecialchars($INPUT->str('rtmemail'))."';\n");
            fwrite($fp, '?>');
            fclose($fp);
            echo $this->getLang('your_email_add').htmlspecialchars($INPUT->str('rtmemail')).$this->getLang('is_now_saved');
            // Accept the action
            $event->preventDefault();
            $event->stopPropagation();
            // all work has been done in handleActPreprocess()
            //$event->data = 'profile';
        }
    }
}
