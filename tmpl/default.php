<?php // no direct acces
defined( '_JEXEC' ) or die( 'Restricted access');

$document = JFactory::getDocument();
$moduleclass_sfx = $params->get('moduleclass_sfx', $template); ?>


<ul class="video__list"><!--
  <?php foreach($list as $key => $item) : ?>
--><li class="video">
    <div class="video__wrapper">
      <?=$item->video;?>
    </div>

    <h2 class="video__title"><?=$item->content_title;?></h2>

    <div class="video__content">
      <?=$item->content_introtext;?>
	  
      <a class="<?php echo trim($moduleclass_sfx); ?>__link" href="<?php echo $item->link; ?>">Watch Video&hellip;</a>
    </div>
  </li><!--
  <?php endforeach; ?>
--></ul>