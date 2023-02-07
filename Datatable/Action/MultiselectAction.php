<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Action;

use Exception;

class MultiselectAction extends Action
{
    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @param array|null $attributes
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $value = 'sg-datatables-' . $this->datatableName . '-multiselect-action';

        if (\is_array($attributes)) {
            if (\array_key_exists('href', $attributes)) {
                throw new Exception('MultiselectAction::setAttributes(): The href attribute is not allowed in this context.');
            }

            if (\array_key_exists('class', $attributes)) {
                $attributes['class'] = $value . ' ' . $attributes['class'];
            } else {
                $attributes['class'] = $value;
            }
        } else {
            $attributes['class'] = $value;
        }

        $this->attributes = $attributes;

        return $this;
    }
}
