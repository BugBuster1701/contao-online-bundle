<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Online Bundle
 * @link       https://github.com/BugBuster1701/contao-online-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

$header = <<<'EOF'
    This file is part of a BugBuster Contao Bundle.

    @copyright  Glen Langer 2024 <http://contao.ninja>
    @author     Glen Langer (BugBuster)
    @package    Contao Online Bundle
    @link       https://github.com/BugBuster1701/contao-online-bundle

    @license    LGPL-3.0-or-later
    For the full copyright and license information,
    please view the LICENSE file that was distributed with this source code.
    EOF;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/contao',
        __DIR__.'/src',
        __DIR__.'/ecs.php',
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => $header,
        'comment_type' => 'comment',
        'location' => 'after_declare_strict',
    ])
    ->withSkip([
        DeclareStrictTypesFixer::class => [
            'contao/dca/tl_online_session.php',
        ],
        ArraySyntaxFixer::class => [
            'contao/dca/tl_online_session.php',
        ],
    ])
    ->withParallel()
    ->withSpacing(Option::INDENTATION_SPACES, "\n")
    // ->withCache(sys_get_temp_dir().'/ecs/ecs')
;