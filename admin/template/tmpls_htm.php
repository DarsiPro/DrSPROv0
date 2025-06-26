<?php
/**
* @project    DarsiPro CMS
* @package    Footer html
* @url        https://darsi.pro
*/
?>
    <!-- Шаблоны созданные пользователем или создателями шаблона -->
                            <?php echo __('Your templates') ?>
                            <?php foreach ($custom_tpl as $mod => $files): $title = ('default' == $mod) ? __('Default') : __($mod,false,$mod); ?>
                                <div class="tbn<?php if ($type == 'html' && $module == $mod) {$name_tpl=$title; echo ' active';} ?>">
                                    <b><?php echo $title; ?></b>
                                </div>
                                
                                <?php if (!empty($title)):?>
                                    <?php foreach ($files as $file => $title): ?>
                                        <div class="tba<?php if($module == $mod && $title == $filename) {$name_stpl=$title;echo ' silver-bg" id="stem';} ?>">
                                            <a href="design.php?d=html&t=<?php echo $title; ?>&m=<?php echo $mod; ?>" class="red">
                                                <img alt="" align="absmiddle" border="0" src="/panel/design/img/old-browsers/stripes-white-10.png" width="20" height="16">
                                                <span><?php echo $title; ?></span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <!-- Стили шаблона -->
                            <div class="tbn<?php if ($type == 'css') echo ' active' ?>">
                                <b><?php echo __('Style(CSS)') ?></b>
                            </div>
                            <?php foreach ($styles as $f_name): ?>
                                <div class="tba<?php if($f_name == $filename) {$name_stpl=$f_name;echo ' silver-bg" id="stem';} ?>">
                                    <a href="design.php?d=css&t=<?php echo $f_name; ?>" class="orange">
                                        <img alt="" align="absmiddle" border="0" src="/panel/design/img/old-browsers/stripes-white-10.png" width="20" height="16">
                                        <span><?php echo $f_name; ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- javascript шаблона -->
                            <div class="tbn<?php if ($type == 'js') echo ' active' ?>">
                                <b><?php echo __('Scripts(JS)') ?></b>
                            </div>
                            <?php foreach ($scripts as $f_name): ?>
                                <div class="tba<?php if($f_name == $filename) {$name_stpl=$f_name;echo ' silver-bg" id="stem';} ?>">
                                    <a href="design.php?d=js&t=<?php echo $f_name; ?>" class="blue">
                                        <img alt="" align="absmiddle" border="0" src="/panel/design/img/old-browsers/stripes-white-10.png" width="20" height="16">
                                        <span><?php echo $f_name; ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Необходимые для работы модуля шаблоны -->
                            
                            <?php foreach ($allowedFiles as $mod => $files): $title = ('default' == $mod) ? __('Default') : __($mod,false,$mod); ?>
                                <div class="tbn<?php if ($type == 'html' && $module == $mod) {$name_tpl=$title; echo ' active';} ?>">
                                    <b><?php echo $title; ?></b>
                                </div>
                                
                                <?php if (!empty($title)):?>
                                    <?php foreach ($files as $file => $title): ?>
                                        <div class="tba<?php if($module == $mod && $file == $filename) {$name_stpl=$title;echo ' silver-bg" id="stem';} ?>">
                                            <a title="<?php echo $file; ?>" href="design.php?d=html&t=<?php echo $file; ?>&m=<?php echo $mod; ?>">
                                                <img alt="" align="absmiddle" border="0" src="/panel/design/img/old-browsers/stripes-white-10.png" width="20" height="16">
                                                <span><?php echo $title; ?></span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>