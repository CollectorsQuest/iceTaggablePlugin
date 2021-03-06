<?php

class IceTaggableToolkit
{
  /**
   * "Cleans" a string in order it to be used as a tag. Intended for strings
   * representing a single tag
   *
   * @param   string  $tag
   * @return  bool
   */
  public static function cleanTagName($tag)
  {
    return trim(rtrim(str_replace(',', ' ', $tag)));
  }

  /**
   * "Cleans" a string in order it to be used as a tag
   * Intended for strings representing a single tag
   *
   * @param   mixed  $tag
   * @return  mixed
   */
  public static function explodeTagString($tag)
  {
    if (is_string($tag) && (false !== strpos($tag, ',') || preg_match('/\n/', $tag)))
    {
      $tag = preg_replace('/\r?\n/', ',', $tag);
      $tag = explode(',', $tag);
      $tag = array_map('trim', $tag);
      $tag = array_map('rtrim', $tag);
    }

    return (is_array($tag)) ? self::array_iunique($tag) : $tag;
  }

  /**
   * Extract triple tag values from tag.  Returned array will contain four
   * elements: tagname (same as input), namespace, key and value.
   *
   * @param   string  $tag
   * @return  array
   */
  public static function extractTriple($tag)
  {
    $match = preg_match('/^([A-Za-z][A-Za-z0-9_]*):([A-Za-z][A-Za-z0-9_]*)=(.*)$/', $tag, $triple);

    if ($match)
    {
      return $triple;
    }
    else
    {
      return array($tag, null, null, null);
    }
  }

  /**
   * Formats a tag string/array in a pretty string. For instance, will convert
   * tag3,tag1,tag2 into the following string : "tag1", "tag2" and "tag3"
   *
   * @param   array   $tags
   * @return  string
   */
  public static function formatTagString($tags)
  {
    $result = '';
    $sf_i18n = sfContext::getInstance()->getI18n();

    if (is_string($tags))
    {
      $tags = explode(',', $tags);
    }

    $nb_tags = count($tags);

    if ($nb_tags > 0)
    {
      sort($tags, SORT_LOCALE_STRING);
      $i = 0;

      foreach ($tags as $tag)
      {
        $result .= '"'.$tag.'"';
        $i++;

        if ($i == $nb_tags - 1)
        {
          $result .= ' '.$sf_i18n->__('and').' ';
        }
        elseif ($i < $nb_tags)
        {
          $result .= ', ';
        }
      }
    }

    return $result;
  }

  /**
   * Returns true if the passed model name is taggable
   *
   * @param   mixed    $model
   * @return  boolean
   */
  public static function isTaggable($model)
  {
    if (is_object($model))
    {
      $model = get_class($model);
    }

    if (!is_string($model))
    {
      throw new Exception('The param passed to the method isTaggable must be an object or a string.');
    }

    $base_class = sprintf('Base%s', ucfirst($model));
    $callables = sfMixer::getCallables($base_class.':save:post');
    $callables_count = count($callables);
    $i = 0;
    $is_taggable = false;

    while (!$is_taggable && ($i < $callables_count))
    {
      $callable = $callables[$i][0];
      $is_taggable = (is_object($callable)  && (get_class($callable) == 'IceTaggableBehavior'));
      $i++;
    }

    return $is_taggable;
  }

  /**
   * Normalizes a tag cloud, ie. changes a (tag => weight) array into a
   * (tag => normalized_weight) one. Normalized weights range from -2 to 2.
   *
   * @param   array  $tag_cloud
   * @return  array
   */
  public static function normalize($tag_cloud)
  {
    $tags = array();
    $levels = 5;
    $power = 0.7;

    if (count($tag_cloud) > 0)
    {
      $max_count = max($tag_cloud);
      $min_count = min($tag_cloud);
      $max = intval($levels / 2);

      if ($max_count != 0)
      {
        foreach ($tag_cloud as $tag => $count)
        {
          $tags[$tag] = round(.9999 * $levels * (pow($count/$max_count, $power) - .5), 0);
        }
      }
    }

    return $tags;
  }

  public static function weight_tags($tags, $steps = 6)
  {
    $min = 1e9;
    $max = -1e9;

    foreach ($tags as $tag => $value)
    {
      if (is_array($value)) {
        $count = &$tags[$tag]["count"];
      } else {
        $count = &$tags[$tag];
      }
      $count = log($count);

      $min = min($min, $count);
      $max = max($max, $count);

      unset($count);
    }

    // Note: we need to ensure the range is slightly too large to make sure even
    // the largest element is rounded down.
    $range = max(.01, $max - $min) * 1.0001;

    foreach ($tags as $tag => $value)
    {
      if (is_array($value)) {
        $count = &$tags[$tag]["count"];
      } else {
        $count = &$tags[$tag];
      }
      $count = 1 + floor($steps * ($count - $min) / $range) - 3;
      unset($count);
    }

    return $tags;
  }

  public static function in_iarray($str, $a)
  {
    foreach($a as $v)
    {
      if(strcasecmp($str, $v) == 0)
      {
        return true;
      }
    }

    return false;
  }

  public static function array_iunique($a)
  {
    $n = array();

    if (is_array($a))
    {
      foreach ($a as $k => $v)
      if (!self::in_iarray($v, $n))
      {
        $n[$k] = $v;
      }
    }
    else if (is_string($a))
    {
      $n[] = $a;
    }

    return $n;
  }
}
