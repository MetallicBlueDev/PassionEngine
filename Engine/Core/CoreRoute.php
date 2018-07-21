<?php

namespace TREngine\Engine\Core;

/**
 * 
 *
 * @author Sébastien Villemain
 */
class CoreRoute
{

    /**
     * Mode de mise en page courante.
     *
     * @var string
     */
    private $layout = CoreLayout::DEFAULT_LAYOUT;

    /**
     * Détermine si l'affichage se fait en écran complet (affichage classique).
     *
     * @return bool true c'est en plein écran.
     */
    public function isDefaultLayout(): bool
    {
        return (($this->layout === CoreLayout::DEFAULT_LAYOUT) ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé module.
     *
     * @return bool true c'est un affichage de module uniquement.
     */
    public function isModuleLayout(): bool
    {
        return (($this->layout === CoreLayout::MODULE || $this->layout === CoreLayout::MODULE_PAGE) ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé block.
     *
     * @return bool true c'est un affichage de block uniquement.
     */
    public function isBlockLayout(): bool
    {
        return (($this->layout === CoreLayout::BLOCK || $this->layout == CoreLayout::BLOCK_PAGE) ? true : false);
    }

    /**
     * Vérification et assignation du layout.
     */
    public function requestLayout(): void
    {
        // Assignation et vérification de fonction layout
        $layout = strtolower(CoreRequest::getWord(CoreLayout::REQUEST_LAYOUT));

        // Configuration du layout
        if ($layout !== CoreLayout::DEFAULT_LAYOUT && $layout !== CoreLayout::MODULE_PAGE && $layout !== CoreLayout::BLOCK_PAGE && (($layout !== CoreLayout::MODULE && $layout !== CoreLayout::BLOCK) || (!CoreHtml::getInstance()->javascriptEnabled()))) {
            $layout = CoreLayout::DEFAULT_LAYOUT;
        }

        $this->layout = $layout;
    }
}