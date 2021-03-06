<?php if($widget->options['type'] === 'body'){ ?>
    <?php if($this->hasBlock('before_body')){ ?>
        <div class="icms-body-toolbox">
            <?php $this->block('before_body'); ?>
        </div>
    <?php } ?>
    <?php $this->body(); ?>
    <?php $this->block('after_body'); ?>
<?php } elseif($widget->options['type'] === 'breadcrumbs') { ?>
    <?php $this->breadcrumbs($widget->options['breadcrumbs']); ?>
<?php } elseif($widget->options['type'] === 'smessages') { ?>
    <?php if($widget->options['session_type'] === 'toastr'){ ?>

        <?php $this->addTplCSSName([
            'toastr'
        ]); ?>
        <?php $this->addTplJSName([
            'vendors/toastr/toastr.min',
        ]); ?>

        <?php ob_start(); ?>
        <script>
            toastr.options = {progressBar: true, preventDuplicates: true, timeOut: 3000, newestOnTop: true, closeButton: true, hideDuration: 600};
            <?php if ($messages){ ?>
                $(function(){
                <?php foreach($messages as $message){ ?>
                    toastr.<?php echo $message['class']; ?>('<?php echo str_replace(["\n", "'"], ' ', $message['text']); ?>');
                 <?php } ?>
                });
             <?php } ?>
        </script>
        <?php $this->addBottom(ob_get_clean()); ?>
    <?php } else { ?>
        <?php if ($messages){ foreach($messages as $message){ ?>
        <div class="alert alert-<?php echo str_replace(['error'], ['danger'], $message['class']); ?> alert-dismissible fade show" role="alert">
            <?php echo $message['text']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php } } ?>
    <?php } ?>
<?php } elseif($widget->options['type'] === 'copyright') { ?>
    <div class="d-flex align-items-center text-muted icms-links-inherit-color">
        <a href="<?php echo $this->options['owner_url'] ? $this->options['owner_url'] : href_to_home(); ?>">
            <?php html($this->options['owner_name'] ? $this->options['owner_name'] : cmsConfig::get('sitename')); ?>
        </a>
        <span class="mx-2">
            &copy; <?php echo $this->options['owner_year'] ? $this->options['owner_year'] : date('Y'); ?>
        </span>
        <span class="d-none d-sm-block mr-2">
            <?php echo LANG_POWERED_BY_INSTANTCMS; ?>
        </span>
        <?php if ($config->debug && cmsUser::isAdmin()){ ?>
            <a href="#debug_block" data-style="xl" title="<?php echo LANG_DEBUG; ?>" class="ajax-modal">
                <?php echo LANG_DEBUG; ?>
            </a>
        <?php } ?>
    </div>
<?php } elseif($widget->options['type'] === 'site_closed') { ?>
    <div id="site_off_notice" class="py-2 icms-links-inherit-color">
        <?php echo html_svg_icon('solid', 'exclamation-triangle'); ?>
        <?php if (cmsUser::isAdmin()){ ?>
            <?php printf(ERR_SITE_OFFLINE_FULL, href_to('admin', 'settings', 'siteon')); ?>
        <?php } else { ?>
            <?php echo ERR_SITE_OFFLINE; ?>
        <?php } ?>
    </div>
<?php } elseif($widget->options['type'] === 'logo') { ?>
    <?php if($core->uri) { ?>
        <a class="navbar-brand mr-3 flex-shrink-0" href="<?php echo href_to_home(); ?>">
            <img src="<?php echo $logos['small_logo']; ?>" class="d-sm-none" alt="<?php html($config->sitename); ?>">
            <img src="<?php echo $logos['logo']; ?>" class="d-none d-sm-block" alt="<?php html($config->sitename); ?>">
        </a>
    <?php } else { ?>
        <span class="navbar-brand mr-3 flex-shrink-0">
            <img src="<?php echo $logos['small_logo']; ?>" class="d-sm-none" alt="<?php html($config->sitename); ?>">
            <img src="<?php echo $logos['logo']; ?>" class="d-none d-sm-block" alt="<?php html($config->sitename); ?>">
        </span>
    <?php } ?>
<?php } elseif($widget->options['type'] === 'lang_select') { ?>
        <ul class="nav">
            <li class="nav-item dropdown">
                <a class="nav-link text-warning font-weight-bold dropdown-toggle" data-toggle="dropdown" href="#" role="button">
                    <?php echo strtoupper($current_lang); ?>
                </a>
                <div class="dropdown-menu">
                    <?php foreach ($langs as $lang) { ?>
                        <a class="dropdown-item<?php if($lang == $current_lang){ ?> active<?php } ?>" href="<?php html(($config->language == $lang ? '' : '/'.$lang).$_SERVER['REQUEST_URI']); ?>">
                            <?php echo strtoupper($lang); ?>
                        </a>
                    <?php } ?>
                </div>
            </li>
        </ul>
<?php } ?>