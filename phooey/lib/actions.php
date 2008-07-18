<?php

  # Each action takes the $vars array and returns a new $vars array
  
  function process_contact_form($vars) {
    // Process mail form
    $notice = '<p>All fields are required.</p>';
    $sent = false;
    require_once('PostPostMailer.class.php');
    $mailer = new PostPostMailer();
    $mailer->setNotice('missing', "Please check all of the fields and try again.");
    $mailer->setNotice('success', "Thank you for contacting us. We'll be in touch.");
    if(isset($_POST['send'])) {
      $data = $_POST['mail'];
      $data['R_to'] = join(', ', $vars['to']);
      $sent = $mailer->send($data);
    }
    if($mailer->getNotice() != '') {
      $class = $mailer->getCode() == 'success' ? 'success' : 'note';
      $notice = '<p class="'.$class.'">'.$mailer->getNotice().'</p>';  
    }
    $vars['sent'] = $sent;
    $vars['notice'] = $notice;
    $vars['mailer'] = $mailer;
    return $vars;
  }
  
?>