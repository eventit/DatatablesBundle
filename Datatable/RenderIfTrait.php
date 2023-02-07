<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable;

use Closure;

trait RenderIfTrait
{
    /**
     * Render an object only if conditions are TRUE.
     *
     * @var Closure|null
     */
    protected $renderIf;

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Checks whether the object may be added.
     *
     * @return bool
     */
    public function callRenderIfClosure(array $row = [])
    {
        if ($this->renderIf instanceof Closure) {
            return \call_user_func($this->renderIf, $row);
        }

        return true;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @return Closure|null
     */
    public function getRenderIf()
    {
        return $this->renderIf;
    }

    /**
     * @param Closure|null $renderIf
     *
     * @return $this
     */
    public function setRenderIf($renderIf)
    {
        $this->renderIf = $renderIf;

        return $this;
    }
}
