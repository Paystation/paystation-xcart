<?

/**
 * Paystation Payment module for X-Cart 5.0.13
 *
 * @category  X-Cart 5
 * @author    Paystation Ltd <info@paystation.co.nz>
 * @copyright Copyright (c) 2014 Paystation Ltd <info@paystation.co.nz>. All rights reserved
 * @license  
 * @link      http://www.paystation.co.nz
 */

namespace XLite\Module\Paystation\ThreeParty;

/**
 * Module description
 *
 * @package XLite
 */
abstract class Main extends \XLite\Module\AModule {

    /**
     * Author name
     *
     * @return string
     */
    public static function getAuthorName() {
        return 'Paystation';
    }

    /**
     * Module name
     *
     * @return string
     */
    public static function getModuleName() {
        return 'Paystation';
    }

    /**
     * Get module major version
     *
     * @return string
     */
    public static function getMajorVersion() {
        return '5.0';
    }

    /**
     * Module version
     *
     * @return string
     */
    public static function getMinorVersion() {
        return 0;
    }

    /**
     * Module description
     *
     * @return string
     */
    public static function getDescription() {
        return 'Paystation three-party payment module';
    }
    
    public static function getIconURL() {
        return 'image.php?target=module&author=Paystation&name=ThreeParty';
        
    }
}
