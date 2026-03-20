<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Check_Security_Node extends Node
{
    /**
     * @param array<string, int> $usedFilters
     * @param array<string, int> $usedTags
     * @param array<string, int> $usedFunctions
     */
    public function __construct(private readonly array $used_filters, private readonly array $used_tags, private readonly array $used_functions)
    {
        parent::__construct();
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->write("\n")->write("public function checkSecurity()\n")->write("{\n")->indent()->write('static $tags = ')->repr(array_filter($this->used_tags))->raw(";\n")->write('static $filters = ')->repr(array_filter($this->used_filters))->raw(";\n")->write('static $functions = ')->repr(array_filter($this->used_functions))->raw(";\n\n")->write("try {\n")->indent()->write("\$this->sandbox->checkSecurity(\n")->indent()->write('')->repr(array_keys($this->used_tags))->raw(",\n")->write('')->repr(array_keys($this->used_filters))->raw(",\n")->write('')->repr(array_keys($this->used_functions))->raw(",\n")->write("\$this->source\n")->outdent()->write(");\n")->outdent()->write("} catch (SecurityError \$e) {\n")->indent()->write("\$e->setSourceContext(\$this->source);\n\n")->write("if (\$e instanceof SecurityNotAllowedTagError && isset(\$tags[\$e->getTagName()])) {\n")->indent()->write("\$e->setTemplateLine(\$tags[\$e->getTagName()]);\n")->outdent()->write("} elseif (\$e instanceof SecurityNotAllowedFilterError && isset(\$filters[\$e->getFilterName()])) {\n")->indent()->write("\$e->setTemplateLine(\$filters[\$e->getFilterName()]);\n")->outdent()->write("} elseif (\$e instanceof SecurityNotAllowedFunctionError && isset(\$functions[\$e->getFunctionName()])) {\n")->indent()->write("\$e->setTemplateLine(\$functions[\$e->getFunctionName()]);\n")->outdent()->write("}\n\n")->write("throw \$e;\n")->outdent()->write("}\n\n")->outdent()->write("}\n");
    }
}