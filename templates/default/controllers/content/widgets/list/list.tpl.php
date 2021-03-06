<?php
/**
 * Template Name: LANG_WD_CONTENT_LIST_STYLE_BASIC
 * Template Type: widget
 */

if ($items){ ?>

    <div class="widget_content_list">
        <?php foreach($items as $item) { ?>

            <?php
                $url        = href_to($ctype['name'], $item['slug'] . '.html');
                $is_private = $item['is_private'] && $hide_except_title && !$item['user']['is_friend'];
                $image      = (($image_field && !empty($item[$image_field])) ? $item[$image_field] : '');
                if ($is_private) {
                    if($image_field && !empty($item[$image_field])){
                        $image = default_images('private', 'small');
                    }
                    $url    = '';
                }
            ?>

            <div class="item">
                <?php if ($image) { ?>
                    <div class="image">
                        <?php if ($url) { ?>
                            <a href="<?php echo $url; ?>"><?php echo html_image($image, 'small', $item['title']); ?></a>
                        <?php } else { ?>
                            <?php echo html_image($image, 'small', $item['title']); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div class="info">
                    <div class="title">
                        <?php if ($url) { ?>
                            <a href="<?php echo $url; ?>"><?php html($item['title']); ?></a>
                        <?php } else { ?>
                            <?php html($item['title']); ?>
                        <?php } ?>
                        <?php if ($item['is_private']) { ?>
                            <span class="is_private" title="<?php html(LANG_PRIVACY_HINT); ?>"></span>
                        <?php } ?>
                    </div>
                    <?php $teaser = !empty($fields[$teaser_field]) ? $fields[$teaser_field]['handler']->setItem($item)->parse($item[$teaser_field]) : false; ?>
                    <?php if ($teaser) { ?>
                        <div class="teaser">
                            <?php if (!$is_private) { ?>
                                <?php echo string_short($teaser, $teaser_len); ?>
                            <?php } else { ?>
                                <div class="private_field_hint"><?php echo LANG_PRIVACY_PRIVATE_HINT; ?></div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <?php if ($is_show_details) { ?>
                        <div class="details">
                            <span class="author">
                                <a href="<?php echo href_to_profile($item['user']); ?>"><?php html($item['user']['nickname']); ?></a>
                                <?php if ($item['parent_id']){ ?>
                                    <?php echo LANG_WROTE_IN_GROUP; ?>
                                    <a href="<?php echo rel_to_href($item['parent_url']); ?>"><?php html($item['parent_title']); ?></a>
                                <?php } ?>
                            </span>
                            <span class="date">
                                <?php html(string_date_age_max($item['date_pub'], true)); ?>
                            </span>
                            <?php if($ctype['is_comments']){ ?>
                                <span class="comments">
                                    <?php if ($url) { ?>
                                        <a href="<?php echo $url . '#comments'; ?>" title="<?php echo LANG_COMMENTS; ?>">
                                            <?php echo intval($item['comments']); ?>
                                        </a>
                                    <?php } else { ?>
                                        <?php echo intval($item['comments']); ?>
                                    <?php } ?>
                                </span>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

        <?php } ?>
    </div>

<?php } ?>