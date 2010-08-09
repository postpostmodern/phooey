<?
# parser.inc
// Siple Template Parser is free software; you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published
// by the Free Software Foundation; either version 2 of the License,
// or (at your option) any later version.
// PixHound is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston,
// MA  02111-1307  USA
//
// © 2005 Stefan Reich / Tobi Schulz
// http://www.script.gr/scripts/STP/

class Parser2 {
  # Do NOT call variables from the outside ! (Use the public methods instead.)
  var $params = array();
  var $paramObject;
  var $template;
  var $output; // NULL with parseAndEcho, Array otherwise
  var $includePath;

  ########################################################
  ################## Internal Functions ##################
  ########################################################
  
  ## checkCondition()
  ## Function to check for conditions in IF Tags
  ## Possible conditions:
  #
  #  gt = greater (Numbers only)
  #  ge = greater or equal (Numbers only)
  #  lt = lower (Numbers only)
  #  le = lower or equal (Numbers only)
  #  eq = equal (Number or string)
  #  ne = not equal (Number or string)
  #  lk = existis in string (String only, functions like the 
  #       SQL LIKE '%string%'
  #
  # $value = value to check condition against
  #
  # 
  
  function checkCondition($value, $condition) {
    ereg("([^ ]+) (.+)",$condition,$cond);
    $chh = $cond[1];
    $wert = $cond[2];
    $wert = ereg_replace("['|\"]","",$wert);
    if($chh == "gt") return ($value >  $wert);
    if($chh == "ge") return ($value >= $wert);
    if($chh == "lt") return ($value <  $wert);
    if($chh == "le") return ($value <= $wert);
    if($chh == "eq") return ($value == $wert);
    if($chh == "ne") return ($value != $wert);
    if($chh == "lk") return eregi($wert,$value);
    return $wert;
  }

  ## colorSet(string $colorstring)
  ## Enables changing Colors f.e. in table rows
  ## initiating an array with the color values
  ## as given by the template in form
  ## <#ATTR COLOR1,COLOR2,COLOR3...#> 
  ## 
  #
  function colorSet($colorstring)  {
    $colorstring = ereg_replace(" +","",$colorstring);
    $this->colors = split(",", $colorstring);
    $this->colorindex = 0;
    }
  
  ## colorChange()
  ## prints out the current value of array  $this->colors 
  ## Steps to next index or 0 if end is reached
  function colorChange()  {
    $currentColor = $this->colors[$this->colorindex];
    $this->colorindex = ($this->colorindex == (count($this->colors) - 1)) ? 0 : $this->colorindex + 1;
    return $currentColor;
    }
    
  # Splits the template $str into parts saves it to $this->template.
  # Afterwards each element contains either
  # - Only text
  # - an HTML comment
  # - exactly one parser tag
  function splitTemplate($str) {
    $sicherheitscounter = 0;
    $this->template = array();
    while ($str != '') {
      $result = preg_match('/^(.*?)(<#.*?#>|<!--.*?-->|\n?$)/s', $str, $matches);
      $str = substr($str, strlen($matches[0]));
      if (strlen($matches[1])) $this->template[] = $matches[1];
      if (preg_match('/<#INCLUDE (.*)#>/', $matches[2], $matches2)) {
        $str = $this->loadInclude($matches2[1]).$str;
      } 
      elseif (preg_match('/<#ATTR (.*)#>/', $matches[2], $matches2))  {
        $this->colorSet($matches2[1]);
      }
      else {
        if (strlen($matches[2])) $this->template[] = $matches[2];
      }
      if (++$sicherheitscounter >= 2000) {
        print_r($matches);
        die("Parser stuck: '".$str."' $result ".strlen($str));
      }
    }
  }
  
  # loads an Include file and returns its contents
  function loadInclude($name) {
    $path = $this->includePath.$name;
    return file_get_contents($path);
  }

  # calls a variable or constant
  function getvar(&$vars, $var) {
    if ($var == "ATTR")
      return $this->colorChange();
    elseif (array_key_exists($var, $vars))
      return $vars[$var];
    elseif ($this->paramObject)
      return $this->paramObject->getVar($var);
    elseif (preg_match('/^G_/', $var) && defined($var))
      return constant($var);
    else
      return '';
  }
  
  function findEndOfIF($j, $to, $var, $tag) {
    $nest = 1;
    while ($j < $to) {
      if (preg_match("|^<#IF |", $this->template[$j])) {
        ++$nest;
        #echo "nest+ $nest: ".$this->template[$j]."<br>";
      } elseif (preg_match("|^<#/IF |", $this->template[$j])) {
        --$nest;
        #echo "nest- $nest: ".$this->template[$j]."<br>";
      }
      if ($nest <= 0) break;
      ++$j;
    }
    #while ($j < $to && !preg_match("|<#/IF !?$var#>|", $this->template[$j])) ++$j;
    
    if ($j >= $to) {
      echo "<br>WARNING: $tag not closed<br>"; 
    }
    
    return $j;
  }
  
  # works its way through the entries  $this->template[$from] until $this->template[$to-1]
  # using the parameters $vars and appends the result to $this->output
  # $enable: Output mode: 0=disabled; 1=active; -1=disabled, to be enabled with ELSE 
  function process($from, $to, $vars, $enable = 1) {
    for ($i = $from; $i < $to; $i++) {
      $p = $this->template[$i];
      if ($enable != 1) {
        # nur nach ELSE und geschachtelten IFs suchen
        if ($p == "<#ELSE#>") {
          $enable = -$enable;
        } elseif (preg_match('/^<#IF (!?)(.*)?#>/', $p, $matches)) {
          $var = $matches[2];
          if (preg_match('/(\S*)\s+\(?(.*?)\)?$/', $var, $matches))
            $var = $matches[1];
          
          ++$i;
          $j = $this->findEndOfIF($i, $to, $var, $p);
          
          # call process() recursively but don't output anything
          $this->process($i, $j, $vars, 0);
            
          # Proceed after closing tag
          $i = $j;
        }        
      } elseif (preg_match("/^<#FOR (.*)#>/", $p, $matches)) {
        # find ends of FOR tags
        $var = $matches[1];
        $value = $this->getvar($vars, $var);
        $j = ++$i;
        while ($j < $to && $this->template[$j] != "<#/FOR $var#>") ++$j;
        if ($j >= $to) die("Schließendes Tag für $p fehlt");
        
        # call process() recursively for each line
        if (is_array($value)) foreach ($value as $row) {
          if (!is_array($row)) $row = array('ROW' => $row);
          $this->process($i, $j, $row + $vars, 1);
        }
          
        # Proceed after closing tag
        $i = $j;
      } elseif (preg_match('/^<#IF (!?)(.*)?#>/', $p, $matches)) {
        # Split tag
        $neg = $matches[1];
        $var = $matches[2];
        $cond = '';
        if (preg_match('/(\S*)\s+\(?(.*?)\)?$/', $var, $matches)) {
          $var = $matches[1];
          $cond = $matches[2];
        }
        $value = $this->getvar($vars, $var);
        if ($neg) $value = !$value;
        if ($cond) $value = $this->checkCondition($value, $cond);
        
        ++$i;
        $j = $this->findEndOfIF($i, $to, $var, $p);
        
        # call process() recursively if variable is set
        $this->process($i, $j, $vars, $value ? 1 : -1);
          
        # Proceed after closing tag
        $i = $j;
      } elseif ($p == "<#ELSE#>") {
        $enable = -$enable;
      } elseif (preg_match("/^<#(.*)#>/", $p, $matches)) {
        # Variablen-Wert ausgeben
        $this->append($this->getvar($vars, $matches[1]));
      } else { # Normaler Text
        $this->append($p);
      }
    }
  }

  ## Prints PHP code to the output page
  function CheckPHP($text) {
  eval('?>'.$text.'<?'); 
  }
  
  function append($text) {
    if (is_array($this->output))
      $this->output[] = $text;
    else
      echo $text;
  }  

  ########################################################
  ################ PUBLIC FUNCTIONS ################
  ########################################################
  
  # constructor
  function Parser2() {
    global $ADM_SESS;
    if(isset($ADM_SESS['PERM_USERNAME'])) $this->params['ADMIN_USERNAME'] = $ADM_SESS['PERM_USERNAME'];
  }
  
  # Returns all set parameters
  function getParams() {
    return $this->params;
  }
  
  # Returns ONE set parameter
  function getParam($name) {
    return $this->params[$name];
  }

  # Sets one parameter
  function setParam($name, $value) {
    $this->params[$name] = $value;
  }
  
  # sets several parameters at once
  # accepts an array or an object that supports the method getVar($name)
  function setParams(&$params) {
    if (is_array($params))
      $this->params = $params + $this->params;
    elseif (is_object($params))
      $this->paramObject = $params;
  }
  
  # Deletes all parameters (no argument)
  # or a list of parameters from an array
  # (the parameters can be keys or values)
  function clearParams($array = 'all') {
    if ($array == 'all') {
      $this->params = array();
      $this->paramObject = null;
    } else {
      foreach ($array as $k => $v) {
        unset($this->params[$k]);
        unset($this->params[$v]);
      }
    }
  }
  
  # Deletes one parameter
  function clearParam($name) {
    unset($this->params[$name]);
  }
  
  # Assembles a template from a frame document and fragments
  function assemble($frame, $frags) {
    $tmpl = file_get_contents($frame);
    
    foreach ($frags AS $fragname => $fragpath) {
      $cmd = "|<!--INSERT_$fragname-->|";
      if (preg_match($cmd, $tmpl)) {
  	    $tmpl = preg_replace($cmd, file_get_contents($fragpath), $tmpl);
      }
    }
    
    $this->splitTemplate($tmpl);
  }
  
  # Load a monolithic template
  function setTemplate($tmpl) {
    if (!is_file($tmpl))
      die("Template not found: $tmpl");
    $idx = strrpos($tmpl, '/');
    if (!isset($this->includePath))
      $this->includePath = substr($tmpl, 0, $idx === false ? 0 : $idx+1);
    $this->splitTemplate(file_get_contents($tmpl));
  }
  
  # Sets the template content directly (not through a file)
  function setTemplateText($text) {
    $this->splitTemplate($text);
  }
  
  # Parse template and return the contents
  function parseAndReturn() {
    $this->output = array();
    $this->process(0, count($this->template), $this->params);
    return join('', $this->output);
  }
  
  # Parse template and ECHO the result
  function parseAndEcho() {
    $this->output = null;
    $this->process(0, count($this->template), $this->params);
  }
  
  # Parse template and ECHO the result;
  # Eval One-line PHP code
  function parseAndEchoPHP() {
    $this->CheckPHP($this->parseAndReturn());
  }
  
  # Parse template and save the result to the file $file
  function parseAndSave($file) {
    $outf = fopen($file, "w");
    fputs($outf, $this->parseAndReturn());
    fclose($outf);
  }
  
  # Set include path (only 1 directory possible)
  # Cal this before setTemplate!
  function setIncludePath($path) {
    $this->includePath = $path;
    if (substr($path, -1, 1) != '/') $this->includePath .= '/';
  }
  
  ########################################################
  ################# STATIC FUNCTIONS #################
  ########################################################
  
  # Does everything at once: assemble, setParams and parseAndEcho
  function assembleAndEcho($frame, $frags, $params) {
    $parser = new Parser2;
    $parser->assemble($frame, $frags);
    $parser->setParams($params);
    $parser->parseAndEcho();
  }
  
  
  # Does everything at once: setTemplate, setParams and parseAndEcho
  function setTemplateAndEcho($tmpl, $params) {
    $parser = new Parser2;
    $parser->setTemplate($tmpl);
    $parser->setParams($params);
    $parser->parseAndEcho();
  }
  
} # End class Parser2

# a parser that uses [ ] instead of <# #>
class AlternativeParser extends Parser2 {
  function splitTemplate($str) {
    $str = preg_replace('/\[(.*?)\]/e', "'<#'.strtolower('\\1').'#>'", $str);
    parent::splitTemplate($str);
  }
}
  
?>
