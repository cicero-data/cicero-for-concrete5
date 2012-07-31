<p>Enter your Cicero username and password</p>
<?php echo $form->label('user_name', 'User Name');?>
<?php echo $form->text('user_name', $user_name, array('style' => 'width: 320px'));?>
<?php echo $form->label('password', 'Password');?>
<?php echo $form->password('password', $password, array('style' => 'width: 320px'));?>
<?php $elections = $this->action('refresh_elections'); ?>
<label for="refresh-elections-link">Click below to refresh the elections from Cicero</label>
<a id="refresh-elections-link" href="<?=$elections?>">Refresh elections</a>
