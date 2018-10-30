<?php

namespace PassionEngine\Engine\Lib;

/**
 * Référence les styles possibles dans le menu.
 *
 * @author Sébastien Villemain
 */
class LibMenuStyle
{

    /**
     * Chemin à utiliser pour appeler la méthode de rendu.
     *
     * @var string
     */
    public const DEFAULT_LINE_RENDERING_METHOD = 'LibMenu::getLine';

    /**
     * Texte en gras.
     *
     * @var string
     */
    public const BOLD = 'BOLD';

    /**
     * Texte en italique.
     *
     * @var string
     */
    public const ITALIC = 'ITALIC';

    /**
     * Texte en souligné.
     *
     * @var string
     */
    public const UNDERLINE = 'UNDERLINE';

    /**
     * Texte en grand.
     *
     * @var string
     */
    public const BIG = 'BIG';

    /**
     * Texte en petit.
     *
     * @var string
     */
    public const SMALL = 'SMALL';

    /**
     * Lien internet.
     *
     * @var string
     */
    public const HYPER_LINK = 'A';

}