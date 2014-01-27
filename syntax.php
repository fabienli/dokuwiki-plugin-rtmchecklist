<?php
/**
 * Info rtmchecklist: Show a button to send a Checklist to RTM.
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Fabien Lisiecki
 *
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
if(!defined('RTMCHECKLIST_IMG_ABSDIR')) define('RTMCHECKLIST_IMG_ABSDIR', DOKU_PLUGIN."rtmchecklist/images");

require_once(DOKU_INC.'inc/search.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_rtmchecklist extends DokuWiki_Syntax_Plugin {

   /**
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    function getType() {
        return 'substition';
    }
    
   /**
    * Where to sort in?
    *
    * @param none
    * @return Integer <tt>999</tt>.
    * @public
    * @static
    */
    function getSort(){
        return 999;
    }

    
   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('<rtmchecklist>.*?</rtmchecklist>',$mode,'plugin_rtmchecklist');
    }
    
    
   /**
    * Handler to prepare matched data for the rendering process.
    *
    * <p>
    * The <tt>$aState</tt> parameter gives the type of pattern
    * which triggered the call to this method:
    * </p>
    * <dl>
    * <dt>DOKU_LEXER_ENTER</dt>
    * <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
    * <dt>DOKU_LEXER_MATCHED</dt>
    * <dd>a pattern set by <tt>addPattern()</tt></dd>
    * <dt>DOKU_LEXER_EXIT</dt>
    * <dd> a pattern set by <tt>addExitPattern()</tt></dd>
    * <dt>DOKU_LEXER_SPECIAL</dt>
    * <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
    * <dt>DOKU_LEXER_UNMATCHED</dt>
    * <dd>ordinary text encountered within the plugin's syntax mode
    * which doesn't match any pattern.</dd>
    * </dl>
    * @param $aMatch String The text matched by the patterns.
    * @param $aState Integer The lexer state for the match.
    * @param $aPos Integer The character position of the matched text.
    * @param $aHandler Object Reference to the Doku_Handler object.
    * @return Integer The current lexer state for the match.
    * @public
    * @see render()
    * @static
    */
    function handle($match, $state, $pos, &$handler){
        $tasks = array();
        $start = false;
        $end   = false;
        switch ($state) {
          case DOKU_LEXER_ENTER : 
            $start = true;
            break;
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            break;
          case DOKU_LEXER_EXIT :
            $end = true;
            break;
          case DOKU_LEXER_SPECIAL :
            $start = true;
            $end   = true;
            $match    = substr($match, 14, -15);
            $tasks = array();
            //split tasks in lines
            $tasks = preg_split('/[\|\n]/u', $match);
            break;
          default:
        }
        return array('tasks' => $tasks, 'start' => $start, 'end' => $end);
    }

    
   /**
    * Handle the actual output creation.
    *
    * <p>
    * The method checks for the given <tt>$aFormat</tt> and returns
    * <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
    * contains a reference to the renderer object which is currently
    * handling the rendering. The contents of <tt>$aData</tt> is the
    * return value of the <tt>handle()</tt> method.
    * </p>
    * @param $aFormat String The output format to generate.
    * @param $aRenderer Object A reference to the renderer object.
    * @param $aData Array The data created by the <tt>handle()</tt>
    * method.
    * @return Boolean <tt>TRUE</tt> if rendered successfully, or
    * <tt>FALSE</tt> otherwise.
    * @public
    * @see handle()
    */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            if($data['start'])
            {
                global $ID;
                $renderer->doc .= '<form action="' . $_SERVER['PHP_SELF'] .'" accept-charset="utf-8" id="form_rtmchecklist" method="post" enctype="multipart/form-data"><lu>';
                $renderer->doc .= '<input type="hidden" name="id" value="' . $ID . '"/>';
                $renderer->doc .= '<input type="hidden" name="do" value="plugin_rtmsend"/>';
                $renderer->doc .= '<input type="hidden" name="sectok" value="'.getSecurityToken().'" />';
            }
            $full_list = "";
            foreach($data['tasks'] as $i=> $item) {
                if(strlen(preg_replace('/[\s\n\r]+/', '', $item))>0) {
                    $renderer->doc .= '<li>'.htmlspecialchars($item).'</li>';
                    $full_list.=htmlspecialchars($item)."\n";
                }
            }
            $renderer->doc .= '<input type="hidden" name="taskslist" value="'.$full_list.'"/>';
            if($data['end'])
            {
                $renderer->doc .= '</lu>';
                //  onclick=\"sendRtmChecklist()\" ?
                $renderer->doc .= '<input type="submit" class="button" value="'.$this->getLang('send').'" ></submit></form><br/ >';
            }
            return true;
        }
        return false;
    }
    
} //rtmchecklist class end  
