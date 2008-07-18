<?php

  # Each action takes the $vars array and returns a new $vars array
  
  function process_contact_form($vars) {
    // Process mail form
    $notice = '';
    $sent = false;
    require_once('PostPostMailer.class.php');
    $mailer = new PostPostMailer();
    $mailer->addField('phone');
    $mailer->setNotice('missing', "Looks like you didn't fill everything in. Please check all of the required fields and try again.");
    $mailer->setNotice('success', "Thanks for contacting us. We will get back with you via phone or email very soon!");
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