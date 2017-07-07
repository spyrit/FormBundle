<?php

namespace Spyrit\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * ArrayToSeparatedStringTransformer
 *
  * @author Charles HENZEL - Spyrit Systemes <charles.henzel@spyrit.net>
 */
class ArrayToSeparatedStringTransformer implements DataTransformerInterface
{
    protected $separator;
    protected $addLastSeparator;
    protected $sort;

    /**
     * @param string $separator default = ','
     * @param string $sort      default = null, values : null, 'asc' or 'desc'
     * @param bool   $addLastSeparator default = true
     */
    public function __construct($separator = ',', $sort = null, $addLastSeparator = true)
    {
        $this->separator = $separator;
        $this->sort = in_array($sort, array('asc', 'desc')) ? $sort : null;
        $this->addLastSeparator = (bool) $addLastSeparator;
    }

    public function transform($array)
    {
        $string = is_array($array) ? implode($this->separator, $array): (string) $array;
        if ($this->addLastSeparator) {
            $string .= $string !== null && $string !== '' ?  $this->separator : '';
        }

        return $string;
    }

    public function reverseTransform($string)
    {
        $array = array();
        if (preg_match('/\s*'.preg_quote($this->separator, '/').'\s*/', $string)) {
            $array = preg_split('/[\s'.preg_quote($this->separator).']+/', $string, null, PREG_SPLIT_NO_EMPTY);
        } elseif ($string !== null && $string !== '') {
            $array = array($string);
        }

        if ($this->sort == 'asc') {
            sort($array);
        } elseif ($this->sort == 'desc') {
            asort($array);
        }

        return $array;
    }
}
