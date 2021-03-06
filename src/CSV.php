<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2016
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace Impensavel\Essence;

use RuntimeException;
use SplFileInfo;
use SplFileObject;

class CSV extends AbstractEssence
{
    /**
     * CSV constructor
     *
     * @param   array  $element Element
     * @throws  EssenceException
     */
    public function __construct(array $element)
    {
        $this->register($element);
    }

    /**
     * Prepare data for extraction
     *
     * @param   mixed  $input  Input data
     * @param   array  $config Configuration settings
     * @throws  EssenceException
     * @return  array|SplFileObject
     */
    protected function prepare($input, array $config)
    {
        if ($input instanceof SplFileInfo) {
            try {
                ini_set('auto_detect_line_endings', $config['auto_eol']);

                $data = $input->openFile('r');

                $data->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
                $data->setCsvControl($config['delimiter'], $config['enclosure'], $config['escape']);
                $data->rewind();

                return $data;
            } catch (RuntimeException $e) {
                throw new EssenceException('Could not open "'.$input->getPathname().'" for parsing', 0, $e);
            }
        }

        if (is_string($input)) {
            $lines = preg_split('/\R/', $input, null, PREG_SPLIT_NO_EMPTY);

            $data = array();

            foreach ($lines as $line) {
                $data[] = str_getcsv($line, $config['delimiter'], $config['enclosure'], $config['escape']);
            }

            return $data;
        }

        if (is_resource($input)) {
            $type = get_resource_type($input);

            if ($type != 'stream') {
                throw new EssenceException('Invalid resource type: '.$type);
            }

            $string = stream_get_contents($input);

            if ($string === false) {
                throw new EssenceException('Failed to read input from stream');
            }

            return $this->prepare($string, $config);
        }

        throw new EssenceException('Invalid input type: '.gettype($input));
    }

    /**
     * {@inheritdoc}
     */
    public function extract($input, array $config = array(), &$data = null)
    {
        $config = array_merge(array(
            'delimiter'  => ',',
            'enclosure'  => '"',
            'escape'     => '\\',
            'start_line' => 0,
            'exceptions' => true,  // Throw exception on invalid columns
            'auto_eol'   => false, // Auto detect end of lines
        ), $config);

        $elements = $this->prepare($input, $config);

        foreach ($elements as $line => $element) {
            // Skip until we reach the starting line
            if ($line < $config['start_line']) {
                continue;
            }

            // Current element properties
            $properties = array();

            foreach ($this->maps['default'] as $key => $column) {
                if (isset($element[$column])) {
                    $properties[$key] = $element[$column];

                    continue;
                }

                // Halt extraction on invalid column
                if ($config['exceptions']) {
                    throw new EssenceException('Invalid column '.$column.' @ line '.$line.' for property "'.$key.'"');
                }
            }

            // Execute element data handler
            $arguments = array(
                $line,
                $properties,
                &$data,
            );

            call_user_func_array($this->handlers['default'], $arguments);
        }

        return true;
    }
}
