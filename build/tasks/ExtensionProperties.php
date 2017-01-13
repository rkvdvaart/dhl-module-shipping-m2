<?php
/**
 * ExtensionProperties Phing Task
 *
 * @category Dhl
 * @package  Dhl\Versenden
 * @author   Christoph Aßmann <christoph.assmann@netresearch.de>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     http://www.netresearch.de/
 */

class ExtensionProperties extends Task
{
    /**
     * Set extension name and version properties.
     */
    public function main()
    {
        $reader = new XMLReader();

        if (!$reader->open(__DIR__ . '/../../etc/module.xml')) {
            throw new BuildException("Failed to open 'module.xml'");
        }

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'module') {
                $this->project->setProperty('extension.name', $reader->getAttribute('name'));
                $this->project->setProperty('extension.version', $reader->getAttribute('setup_version'));
                break;
            }
        }

        $reader->close();
    }
}
