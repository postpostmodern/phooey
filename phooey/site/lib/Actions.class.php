<?php

/**
* Actions process your data before rendering the page
* Designate one or more actions in your page definition (pages.yaml)
*/
class Actions extends PhooeyActions
{
  
  function __construct($page) { parent::__construct($page); }

  # To use values in your views, return them as an array
  
  public function process_contact_form() {
    // Process mail form
    $notice = '<p>All fields are required.</p>';
    $sent = false;
    require_once('PostPostMailer.class.php');
    $mailer = new PostPostMailer();
    $mailer->setNotice('missing', "Please check all of the fields and try again.");
    $mailer->setNotice('success', "Thank you for contacting us. We'll be in touch.");
    if(isset($_POST['send'])) {
      $data = $_POST['mail'];
      $data['R_to'] = join(', ', $this->vars['to']);
      $sent = $mailer->send($data);
    }
    if($mailer->getNotice() != '') {
      $class = $mailer->getCode() == 'success' ? 'success' : 'note';
      $notice = '<p class="'.$class.'">'.$mailer->getNotice().'</p>';  
    }
    return array('sent'      => $sent,
                 'notice'    => $notice,
                 'R_name'    => $mailer->getData('R_name'),
                 'R_from'    => $mailer->getData('R_from'),
                 'R_subject' => $mailer->getData('R_subject'),
                 'R_message' => $mailer->getData('R_message'));
  }
  
}

?>
