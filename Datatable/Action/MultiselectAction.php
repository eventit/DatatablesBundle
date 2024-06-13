<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * (c) stwe <https://github.com/stwe/DatatablesBundle>
 * (c) event it AG <https://github.com/eventit/DatatablesBundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sg\DatatablesBundle\Datatable\Action;

use Closure;
use Exception;
use RuntimeException;

class MultiselectAction extends Action
{
    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @throws Exception
     */
    public function setAttributes(array|Closure|null $attributes): static
    {
        $value = 'sg-datatables-' . $this->datatableName . '-multiselect-action';

        if (\is_array($attributes)) {
            if (\array_key_exists('href', $attributes)) {
                throw new RuntimeException('MultiselectAction::setAttributes(): The href attribute is not allowed in this context.');
            }

            $attributes['class'] = \array_key_exists('class', $attributes) ? $value . ' ' . $attributes['class'] : $value;
        } else {
            $attributes['class'] = $value;
        }

        $this->attributes = $attributes;

        return $this;
    }
}
