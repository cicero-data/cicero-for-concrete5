<p>Enter your Cicero username and password</p>
<?php echo $form->label('user_name', 'User Name');?>
<?php echo $form->text('user_name', $user_name, array('style' => 'width: 320px'));?>
<?php echo $form->label('password', 'Password');?>
<?php echo $form->password('password', $password, array('style' => 'width: 320px'));?>
<?php echo $form->label('bing_key', 'Bing Maps Key');?>
<?php echo $form->text('bing_key', $bing_key, array('style' => 'width: 320px'));?>