<?php
  
  class PostPostMailer 
  {
    # internal variables
    var $message           = false;
    var $domain            = '';
    var $fields            = array('R_to','R_name','R_from','R_subject', 'R_message', 'headers');
    var $custom_fields     = array();
    var $missing_fields    = array();
    var $data              = array();
    var $code              = 'none';
    var $notices           = array(
                      'none'    => '',
                      'missing' => 'Missing info. Please fill out all required fields and send again.',
                      'success' => 'Your message has been sent!',
                      'email'   => 'Your email address appears to be invalid. Please try again.',
                      'name'    => 'Please use only letters in your name. Please try again.',
                      'error'   => 'Sorry. There seems to have been an error sending your message. Please try again later.'
                    );
    var $message_placement = 'before';
    
    # Constructor
    function PostPostMailer ( $domain = false )
    {
      $this->domain = $domain;
    }
    ###
    function addField($field)
    {
      $this->fields[] = $field;
      $this->custom_fields[] = $field;  
      return $this->custom_fields;
    }
    
    function addFields($fields) 
    {
      foreach($fields as $str) {
        $this->fields[] = $str;
        $this->custom_fields[] = $str;
      }
      return $this->custom_fields;
    }
    
    function getData($field)
    {
      return isset($this->data[$field]) ? $this->data[$field] : '';
    }
    
    function checkMissing($field)
    {
      return in_array($field, $this->missing_fields) ? 'missing' : '';
    }
        
    function getNotice()
    {
      return $this->notices[$this->code];
    }
    
    function getCode()
    {
      return $this->code;
    }
    
    function setNotice($code, $notice)
    {
      $this->notices[$code] = $notice;
    }
    
    function setMessagePlacement($where)
    {
      $this->message_placement = $where;
    }
    
    function checkEmail($email)
    {
      return eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$', $email);
    }
    
    function checkName($name)
    {
      return eregi("^[a-z[:space:]'\.-]+$", $name);
    }
    
    function checkHeaders($headers)
    {
      $result = true;
      foreach($headers as $h) {
        if(!eregi("^[a-z[:space:]'\.:<>@/-]+$", $h)) {
          $result = false;
        }
      }
      return $result;
    }
    
    function checkReferer()
    {
      if($this->domain === false) {
        return true;
      }
      return strpos($_SERVER['HTTP_REFERER'], $this->domain) !== false;
    }
    
    function assign($data)
    {
      // Clean up the data
      if(get_magic_quotes_gpc()) {
        array_map("stripslashes", $data);
      }
      array_map("trim", $data);
      // Assign the data to vars
      $this->data = $data;  
    }

    function send($data)
    {
      $this->assign($data);
      // Check to see if any required field (R_) is missing and if so, return false
      foreach($this->fields as $field) {
        if(strpos($field, "R_") === 0 && empty($data[$field])) {
          $this->code = 'missing';
          $this->missing_fields[] = $field;
        }
      }
      if($this->code == 'missing') return false;
      // Check for valid email address
      if(!$this->checkEmail($this->data['R_from'])) {
        $this->code = 'email';
        return false;
      }
      // Check for valid name
      if(!$this->checkName($this->data['R_name'])) {
        $this->code = 'name';
        return false;
      }
      
      // Initialize header array
      $headers = array();
      $headers[] = "From: {$this->data['R_name']} <{$this->data['R_from']}>";
      if(isset($this->data['headers'])) {
        if(is_array($this->data['headers'])) {
          array_merge($headers, $this->data['headers']);
        } else {
          $headers[] = $this->data['headers'];
        }
      }
      // Check for valid headers
      if(!$this->checkHeaders($headers)) {
        $this->code = 'headers';
        $this->notices['headers'] = "There was an error. Please try again later.<!-- ".implode("\r\n", $headers)." -->";
        return false;
      }
      // Combine headers
      $headers = implode("\r\n", $headers);
      
      // Check to see if the form was submitted from the site
      if(!$this->checkReferer()) {
        return false;
      }
      // Add all of the custom fields into the message
      $message = $this->message_placement == 'before' ? $this->data['R_message'] . "\n" : '';
      
      foreach($this->custom_fields as $field) {
        if($this->data[$field] != '') {
          $message .= ucwords(str_replace("-"," ",(str_replace("R_", "", $field))));
          $message .= ": {$this->data[$field]}\n";
        }
      }
      $message .= $message == '' ? '' : "\n";
      $message .= $this->message_placement == 'before' ? '' : $this->data['R_message'];
      // Try to email
      $success = mail($this->data['R_to'], $this->data['R_subject'], $message, $headers);
      if(!$success) {
        $this->code = 'error';
        return false;
      }
      $this->code = 'success';
      foreach($this->data as $field => $value) {
        $this->data[$field] = '';
      }
      return true;
    }
  }
  ###

?>