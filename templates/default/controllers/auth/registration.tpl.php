<?php $this->setPageTitle(LANG_REGISTRATION); ?>

<?php $this->addBreadcrumb(LANG_REGISTRATION); ?>

<h1><?php echo LANG_REGISTRATION; ?></h1>

<?php if (!$this->controller->options['is_reg_enabled']){ ?>

    <p><?php echo $this->controller->options['reg_reason']; ?></p>
    <?php return; ?>

<?php } ?>

<?php
    $this->renderForm($form, $user, array(
        'action' => href_to('auth', 'register'),
        'method' => 'post',
        'append_html' => $captcha_html,
        'submit' => array(
            'title' => LANG_CONTINUE,
            'id' => 'submit_register'
        )
    ), $errors);
?>

<script>

    function toggleGroups(){

        if ($('select#group_id').length == 0){ return false; }

        var group_id = $('select#group_id').val();

        $('.groups-limit').hide().find('input, select, textarea').prop('required', false);

        $('.group-' + group_id).show();

        $('fieldset').each(function(){

           if ($('.field:visible', $(this)).length==0) {
               $(this).hide();
           }

           if ($('.group-' + group_id, $(this)).length>0) {
               $(this).show();
           }

        });

    }

    function userurlUpdate(){

        $("#userurl").text($("#<?php echo $this->controller->options['reg_user_slug']; ?>").val());

    }

    function rulesagreeUpdate(){

        if ($("#rules_agree").length) {

            if ($("#rules_agree").prop('checked')) {
                $("#submit_register").show();
            } else {
                $("#submit_register").hide();
            }

        }

    }

    $(document).ready(function(){

        if ($('select#group_id').length == 0){ return false; }

        $('select#group_id').change(function(){ toggleGroups(); });

    });

    $("#<?php echo $this->controller->options['reg_user_slug']; ?>").keyup(function(){

        userurlUpdate();

    });

   $("#rules_agree").change(function(){

        rulesagreeUpdate();

    });

    toggleGroups();

    userurlUpdate();
    rulesagreeUpdate();


</script>
