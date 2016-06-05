<?php

    $this->setPageTitle(LANG_USERS_EDIT_PROFILE);

    $this->addBreadcrumb(LANG_USERS, href_to('users'));
    $this->addBreadcrumb($profile['nickname'], href_to('users', $user_slug));
    $this->addBreadcrumb(LANG_USERS_EDIT_PROFILE);

    $this->addToolButton(array(
        'class' => 'save',
        'title' => LANG_SAVE,
        'href'  => "javascript:icms.forms.submit()"
    ));

    $this->addToolButton(array(
        'class' => 'cancel',
        'title' => LANG_CANCEL,
        'href'  => href_to('users', $user_slug)
    ));

?>

<?php // Сохраняем правильный (прежний) адрес для вывода табов ?>
<?php $profile_header = $profile ?>
<?php $profile_header['slug'] = $user_slug ?>
<?php $this->renderChild('profile_edit_header', array('profile'=>$profile_header)); ?>

<?php
    $this->renderForm($form, $profile, array(
        'action' => '',
        'method' => 'post',
        'toolbar' => false
    ), $errors);
?>

<script>

    function userurlUpdate(){

        $("#userurl").text($("#<?php echo $this->controller->options['slug_field']; ?>").val());

    }

    $("#<?php echo $this->controller->options['slug_field']; ?>").keyup(function(){

        userurlUpdate();

    });

    userurlUpdate();

</script>
