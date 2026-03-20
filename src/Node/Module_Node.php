<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Source;
/**
 * Represents a module node.
 *
 * If you need to customize the behavior of the generated class, add nodes to
 * the following nodes: display_start, display_end, constructor_start,
 * constructor_end, and class_end.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
final class Module_Node extends Node
{
    /**
     * @param BodyNode $body
     */
    public function __construct(Node $body, ?Abstract_Expression $parent, Node $blocks, Node $macros, Node $traits, $embedded_templates, Source $source)
    {
        if (!$body instanceof Body_Node) {
            trigger_deprecation('twig/twig', '3.12', \sprintf('Not passing a "%s" instance as the "body" argument of the "%s" constructor is deprecated.', Body_Node::class, self::class));
        }
        if (!$embedded_templates instanceof Node) {
            trigger_deprecation('twig/twig', '3.21', \sprintf('Not passing a "%s" instance as the "embedded_templates" argument of the "%s" constructor is deprecated.', Node::class, self::class));
            if (null !== $embedded_templates) {
                $embedded_templates = new Nodes($embedded_templates);
            } else {
                $embedded_templates = new Empty_Node();
            }
        }
        $nodes = ['body' => $body, 'blocks' => $blocks, 'macros' => $macros, 'traits' => $traits, 'display_start' => new Nodes(), 'display_end' => new Nodes(), 'constructor_start' => new Nodes(), 'constructor_end' => new Nodes(), 'class_end' => new Nodes()];
        if (null !== $parent) {
            $nodes['parent'] = $parent;
        }
        // embedded templates are set as attributes so that they are only visited once by the visitors
        parent::__construct($nodes, ['index' => null, 'embedded_templates' => $embedded_templates], 1);
        // populate the template name of all node children
        $this->set_source_context($source);
    }
    public function set_index($index): void
    {
        $this->set_attribute('index', $index);
    }
    public function compile(Compiler $compiler): void
    {
        $this->compile_template($compiler);
        foreach ($this->get_attribute('embedded_templates') as $template) {
            $compiler->subcompile($template);
        }
    }
    protected function compile_template(Compiler $compiler): void
    {
        if (!$this->get_attribute('index')) {
            $compiler->write('<?php');
        }
        $this->compile_class_header($compiler);
        $this->compile_constructor($compiler);
        $this->compile_get_parent($compiler);
        $this->compile_display($compiler);
        $compiler->subcompile($this->get_node('blocks'));
        $this->compile_macros($compiler);
        $this->compile_get_template_name($compiler);
        $this->compile_is_traitable($compiler);
        $this->compile_debug_info($compiler);
        $this->compile_get_source_context($compiler);
        $this->compile_class_footer($compiler);
    }
    protected function compile_get_parent(Compiler $compiler): void
    {
        if (!$this->has_node('parent')) {
            return;
        }
        $parent = $this->get_node('parent');
        $compiler->write("protected function doGetParent(array \$context): bool|string|Template|TemplateWrapper\n", "{\n")->indent()->add_debug_info($parent)->write('return ');
        if ($parent instanceof Constant_Expression) {
            $compiler->subcompile($parent);
        } else {
            $compiler->raw('$this->load(')->subcompile($parent)->raw(', ')->repr($parent->get_template_line())->raw(')');
        }
        $compiler->raw(";\n")->outdent()->write("}\n\n");
    }
    protected function compile_class_header(Compiler $compiler): void
    {
        $compiler->write("\n\n");
        if (!$this->get_attribute('index')) {
            $compiler->write("use Twig\\Environment;\n")->write("use Twig\\Error\\LoaderError;\n")->write("use Twig\\Error\\RuntimeError;\n")->write("use Twig\\Extension\\CoreExtension;\n")->write("use Twig\\Extension\\SandboxExtension;\n")->write("use Twig\\Markup;\n")->write("use Twig\\Sandbox\\SecurityError;\n")->write("use Twig\\Sandbox\\SecurityNotAllowedTagError;\n")->write("use Twig\\Sandbox\\SecurityNotAllowedFilterError;\n")->write("use Twig\\Sandbox\\SecurityNotAllowedFunctionError;\n")->write("use Twig\\Source;\n")->write("use Twig\\Template;\n")->write("use Twig\\TemplateWrapper;\n")->write("\n");
        }
        $compiler->write('/* ' . str_replace('*/', '* /', $this->get_source_context()->get_name()) . " */\n")->write('class ' . $compiler->get_environment()->get_template_class($this->get_source_context()->get_name(), $this->get_attribute('index')))->raw(" extends Template\n")->write("{\n")->indent()->write("private Source \$source;\n")->write("/**\n")->write(" * @var array<string, Template>\n")->write(" */\n")->write("private array \$macros = [];\n\n");
    }
    protected function compile_constructor(Compiler $compiler): void
    {
        $compiler->write("public function __construct(Environment \$env)\n", "{\n")->indent()->subcompile($this->get_node('constructor_start'))->write("parent::__construct(\$env);\n\n")->write("\$this->source = \$this->getSourceContext();\n\n");
        // parent
        if (!$this->has_node('parent')) {
            $compiler->write("\$this->parent = false;\n\n");
        }
        $count_traits = \count($this->get_node('traits'));
        if ($count_traits) {
            // traits
            foreach ($this->get_node('traits') as $i => $trait) {
                $node = $trait->get_node('template');
                $compiler->add_debug_info($node)->write(\sprintf('$_trait_%s = $this->load(', $i))->subcompile($node)->raw(', ')->repr($node->get_template_line())->raw(");\n")->write(\sprintf("if (!\$_trait_%s->unwrap()->isTraitable()) {\n", $i))->indent()->write("throw new RuntimeError('Template \"'.")->subcompile($trait->get_node('template'))->raw(".'\" cannot be used as a trait.', ")->repr($node->get_template_line())->raw(", \$this->source);\n")->outdent()->write("}\n")->write(\sprintf("\$_trait_%s_blocks = \$_trait_%s->unwrap()->getBlocks();\n\n", $i, $i));
                foreach ($trait->get_node('targets') as $key => $value) {
                    $compiler->write(\sprintf('if (!isset($_trait_%s_blocks[', $i))->string($key)->raw("])) {\n")->indent()->write("throw new RuntimeError('Block ")->string($key)->raw(' is not defined in trait ')->subcompile($trait->get_node('template'))->raw(".', ")->repr($node->get_template_line())->raw(", \$this->source);\n")->outdent()->write("}\n\n")->write(\sprintf('$_trait_%s_blocks[', $i))->subcompile($value)->raw(\sprintf('] = $_trait_%s_blocks[', $i))->string($key)->raw(\sprintf(']; unset($_trait_%s_blocks[', $i))->string($key)->raw(']); $this->traitAliases[')->subcompile($value)->raw('] = ')->string($key)->raw(";\n\n");
                }
            }
            if ($count_traits > 1) {
                $compiler->write("\$this->traits = array_merge(\n")->indent();
                for ($i = 0; $i < $count_traits; ++$i) {
                    $compiler->write(\sprintf('$_trait_%s_blocks' . ($i == $count_traits - 1 ? '' : ',') . "\n", $i));
                }
                $compiler->outdent()->write(");\n\n");
            } else {
                $compiler->write("\$this->traits = \$_trait_0_blocks;\n\n");
            }
            $compiler->write("\$this->blocks = array_merge(\n")->indent()->write("\$this->traits,\n")->write("[\n");
        } else {
            $compiler->write("\$this->blocks = [\n");
        }
        // blocks
        $compiler->indent();
        foreach ($this->get_node('blocks') as $name => $node) {
            $compiler->write(\sprintf("'%s' => [\$this, 'block_%s'],\n", $name, $name));
        }
        if ($count_traits) {
            $compiler->outdent()->write("]\n")->outdent()->write(");\n");
        } else {
            $compiler->outdent()->write("];\n");
        }
        $compiler->subcompile($this->get_node('constructor_end'))->outdent()->write("}\n\n");
    }
    protected function compile_display(Compiler $compiler): void
    {
        $compiler->write("protected function doDisplay(array \$context, array \$blocks = []): iterable\n", "{\n")->indent()->write("\$macros = \$this->macros;\n")->subcompile($this->get_node('display_start'))->subcompile($this->get_node('body'));
        if ($this->has_node('parent')) {
            $parent = $this->get_node('parent');
            $compiler->add_debug_info($parent);
            if ($parent instanceof Constant_Expression) {
                $compiler->write('$this->parent = $this->load(')->subcompile($parent)->raw(', ')->repr($parent->get_template_line())->raw(");\n");
            }
            $compiler->write('yield from ');
            if ($parent instanceof Constant_Expression) {
                $compiler->raw('$this->parent');
            } else {
                $compiler->raw('$this->getParent($context)');
            }
            $compiler->raw("->unwrap()->yield(\$context, array_merge(\$this->blocks, \$blocks));\n");
        }
        $compiler->subcompile($this->get_node('display_end'));
        if (!$this->has_node('parent')) {
            $compiler->write("yield from [];\n");
        }
        $compiler->outdent()->write("}\n\n");
    }
    protected function compile_class_footer(Compiler $compiler): void
    {
        $compiler->subcompile($this->get_node('class_end'))->outdent()->write("}\n");
    }
    protected function compile_macros(Compiler $compiler): void
    {
        $compiler->subcompile($this->get_node('macros'));
    }
    protected function compile_get_template_name(Compiler $compiler): void
    {
        $compiler->write("/**\n")->write(" * @codeCoverageIgnore\n")->write(" */\n")->write("public function getTemplateName(): string\n", "{\n")->indent()->write('return ')->repr($this->get_source_context()->get_name())->raw(";\n")->outdent()->write("}\n\n");
    }
    protected function compile_is_traitable(Compiler $compiler): void
    {
        // A template can be used as a trait if:
        //   * it has no parent
        //   * it has no macros
        //   * it has no body
        //
        // Put another way, a template can be used as a trait if it
        // only contains blocks and use statements.
        $traitable = !$this->has_node('parent') && 0 === \count($this->get_node('macros'));
        if ($traitable) {
            if ($this->get_node('body') instanceof Body_Node) {
                $nodes = $this->get_node('body')->get_node('0');
            } else {
                $nodes = $this->get_node('body');
            }
            if (!\count($nodes)) {
                $nodes = new Nodes([$nodes]);
            }
            foreach ($nodes as $node) {
                if (!\count($node)) {
                    continue;
                }
                $traitable = false;
                break;
            }
        }
        if ($traitable) {
            return;
        }
        $compiler->write("/**\n")->write(" * @codeCoverageIgnore\n")->write(" */\n")->write("public function isTraitable(): bool\n", "{\n")->indent()->write("return false;\n")->outdent()->write("}\n\n");
    }
    protected function compile_debug_info(Compiler $compiler): void
    {
        $compiler->write("/**\n")->write(" * @codeCoverageIgnore\n")->write(" */\n")->write("public function getDebugInfo(): array\n", "{\n")->indent()->write(\sprintf("return %s;\n", str_replace("\n", '', var_export(array_reverse($compiler->get_debug_info(), true), true))))->outdent()->write("}\n\n");
    }
    protected function compile_get_source_context(Compiler $compiler): void
    {
        $compiler->write("public function getSourceContext(): Source\n", "{\n")->indent()->write('return new Source(')->string($compiler->get_environment()->is_debug() ? $this->get_source_context()->get_code() : '')->raw(', ')->string($this->get_source_context()->get_name())->raw(', ')->string($this->get_source_context()->get_path())->raw(");\n")->outdent()->write("}\n");
    }
}