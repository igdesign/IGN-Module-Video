<?php
/**
 * Helper class for Stack
 *
 * @package    jevolve.extensions
 * @subpackage Modules
 * @link       http://jevolve.net
 * @license    GNU/GPL, see LICENSE.php
 * mod_stack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

class modVideoHelper
{

  /**
   * Retrieves a list of content items to display
   *
   * @param array $params An object containing the module parameters
   * @access public
   */
  public static function getItem( &$params )
  {


    $db       = JFactory::getDbo();
    $user     = JFactory::getUser();
    $groups   = implode(',', $user->getAuthorisedViewLevels());

    // Preferences
    $categories = $params->get('categories');
    $not_categories = $params->get('not_categories');
    $tags       = $params->get('tags');
    $featured   = $params->get('featured_only', 0);
    $maximum    = $params->get('maximum', 5);
    $order      = $params->get('order', 0);
    $feature_first = $params->get('featured_first', 0);
    $direction  = $params->get('direction', 0);
    $template   = $params->get('template', 'Carousel');
    $use_js     = $params->get('use_js', 1);
    $use_css    = $params->get('use_css', 1);
    $truncate   = $params->get('truncate', 140);
    $itemid     = $params->get('itemid', false);
    $offset     = $params->get('offset', 0);



    $access = !JComponentHelper::getParams('com_content')->get('show_noauth');
    $authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));

    // SELECT
    $qSelect = array();
    $qFrom = array();
    $qJoin = array();
    $qWhere = array();
    $qOrder = array();
    $qLimit = array();

    $qSelect[] = $db->quoteName( 'content.id', 'content_id' );
    $qSelect[] = $db->quoteName( 'content.title', 'content_title' );
    $qSelect[] = $db->quoteName( 'content.alias', 'content_alias' );
    $qSelect[] = $db->quoteName( 'content.introtext', 'content_introtext' );
    $qSelect[] = $db->quoteName( 'content.fulltext', 'content_fulltext' );
    $qSelect[] = $db->quoteName( 'content.images', 'content_images' );


    $qFrom[] = $db->quoteName('#__content', 'content');

    // join category alias to content item for router
    $qSelect[] = $db->quoteName( 'category.id', 'category_id');
    $qSelect[] = $db->quoteName( 'category.alias', 'category_alias');
    $qJoin[] = array('direction' => 'LEFT',
                     'table'     => $db->quoteName('#__categories', 'category'),
                     'on' => $db->quoteName('content.catid').' = '.$db->quoteName('category.id'));

    if ($categories)
    {
      if (count($categories) > 1) {
        $qWhere[] = '('.$db->quoteName('category.id').' = '.implode(' OR '.$db->quoteName('category.id').' = ', $categories).')';
      } else {
        $qWhere[] = $db->quoteName('category.id').' = '. $categories[0];
      }
    }



    if ($not_categories)
    {
      if (count($not_categories) > 1) {
        $qWhere[] = '('.$db->quoteName('category.id').' != '.implode(' AND '.$db->quoteName('category.id').' != ', $not_categories).')';
      } else {
        $qWhere[] = $db->quoteName('category.id').' != '. $not_categories[0];
      }
    }

    if ($tags)
    {
      $qJoin[] = array('direction' => 'LEFT',
                       'table'     => $db->quoteName('#__contentitem_tag_map', 'content_tag'),
                       'on'        => $db->quoteName('content_tag.content_item_id').' = '.$db->quoteName('content.id'));
      if (count($tags) > 1) {
        $qWhere[] = '('.$db->quoteName('content_tag.tag_id').' = '.implode(' OR '.$db->quoteName('content_tag.tag_id').' = ', $tags).')';
      }
      $qWhere[] = $db->quoteName('content_tag.tag_id').' = '. $tags[0];
    }

    // featured only
    if ($featured) {
      $qWhere[] = $db->quoteName('content.featured').' = 1';
    }

    // featured first
    if ($feature_first) {
      $qOrder[] = $db->quoteName('content.featured').' DESC';
    }


    // ordering
    if ($direction == 0) { $direction = 'ASC'; }
    if ($direction == 1) { $direction = 'DESC'; }
    if ($order == 0) { $order = 'ordering'; }
    if ($order == 1) { $order = 'title'; }
    if ($order == 2) { $order = 'created'; }
    if ($order == 3) { $order = 'publish_up'; }
    if ($order)
    {
      $qOrder[] = $db->quoteName('content.'.$order).' '.$direction;
    }

    $qLimit['offset'] = $offset;

    if ($maximum)
    {
      $qLimit['limit'] = $maximum;
    }


    $qJoins = array();
    foreach($qJoin as $join) {
      $qJoins[] = $join['direction'].' JOIN '.$join['table'].' ON '.$join['on'];
    }

/*     print_r($qSelect); */
/*     print_r($qFrom); */
/*     print_r($qJoins); */
/*     print_r($qWhere); */
/*     print_r($qOrder); */
/*     print_r($qLimit); */


    $query = '';
    if (!$qSelect) { return; }
    $query .= 'SELECT '.implode(',', $qSelect);
    if (!$qFrom) { return; }
    $query .= ' FROM '.implode(',', $qFrom);

    if ($qJoins) {
      $query .= ' '.implode(' ', $qJoins);
    }
    if ($qWhere) {
      $query .= ' WHERE '.implode(' AND ', $qWhere);
    }
    if ($qOrder) {
      $query .= ' ORDER BY '.implode(', ', $qOrder);
    }
    $query .= ' LIMIT '.implode(', ', $qLimit);




    // build query and return it
    $db->setQuery($query);


/*     var_dump($db->replacePrefix((string) $db->getQuery())); */
/*     exit; */

    $results = $db->loadObjectList();

    foreach($results as $key => $result)
    {

      $result->content_title = trim($result->content_title);
      $result->text = $result->content_introtext . $result->content_fulltext;
      $result->text = strip_tags($result->text, '<object><iframe>');

      $foundVideos = modVideoHelper::getTag($result->text);
      $result->video = $foundVideos['video'];
      $result->text = $foundVideos['content'];

      $result->content_introtext = modVideoHelper::truncate($result->text, $truncate);

      $results[$key] = $result;

    }

    return $results;
  }

  private static function truncate($text, $chars = 25)
  {
    $trunc = false;
    if (strlen($text) >= $chars) {
      $trunc = true;
    }

    $text = $text." ";
    $text = substr($text,0,$chars);
    $text = substr($text,0,strrpos($text,' '));

    if ($trunc == true) {
      $text = trim($text)."&hellip;";
    }

    return $text;
  }

  public static function getTag($content)
  {

    $match = array();
    $type = null;

    if (strpos($content, '{youtube') !== false) {
      $pattern = "/{.*}/";
      preg_match($pattern, $content, $match);
      $content = preg_replace($pattern, '', $content);

      $match[0] = modVideoHelper::buildEmbed($match[0]);
    }

    if (strpos($content, '<iframe') !== false) {
      $patternFrame = "/<iframe.*?iframe>/";
      preg_match($patternFrame, $content, $match);
      $content = preg_replace($patternFrame, '', $content);
    }

    if (strpos($content, '<object') !== false) {
      $patternObject = "/<object.*?object>/";
      preg_match($patternObject, $content, $match);
      $content = preg_replace($patternObject, '', $content);
    }



    return array('video' => $match[0], 'content' => $content);
  }

  public static function buildEmbed($tag)
  {
    $params = modVideoHelper::parseTag($tag);

    switch ($params[0]) {
      case "youtube":
      default:
        $embed = modVideoHelper::youtube($params);
        break;
    }

    return $embed;
  }


  public static function parseTag($tag)
  {
    $tag = trim($tag, '{}');
    $tag = str_replace('&nbsp;', ' ', $tag);
    $tag = explode(' ', $tag);
    return $tag;
  }


  public static function youtube($params)
  {

    $attributes = array(
                    'id'    => 'ytplayer',
                    'type'  => 'text/html',
/*
                    'width' => '640',
                    'height' => '390',
*/
                    'src'   => 'http://youtube.com/embed/'.$params[1],
                    'origin' => JURI::root(),
                    'frameborder' => '0'
                  );
    $tag = array();
    foreach($attributes as $key=>$value) {
      $tag[] = $key.'="'.$value.'"';
    }

    return '<iframe '.implode($tag, ' ').' ></iframe>';


  }
}