<?php
/**
 * Polythematic Structured Subject Headings System Autocomplete Module
 *
 * PHP version 5
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Jindřich Mynarz <jindrich.mynarz@techlib.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */
require_once 'sys/Autocomplete/Interface.php';

/**
 * Polythematic Structured Subject Headings System Autocomplete Module
 *
 * This class provides suggestions by using Polythematic Structured Subject Headings System.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Jindřich Mynarz <jindrich.mynarz@techlib.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */
class PSHAutocomplete implements AutocompleteInterface
{
    protected $url;

    /**
     * Constructor
     *
     * Establishes base settings for making autocomplete suggestions.
     *
     * @param string $params Additional settings from searches.ini.
     *
     * @access public
     */
    public function __construct($params)
    {
        $this->url = "http://";
    }

    /**
     * getSuggestions
     *
     * This method returns an array of strings matching the user's query for
     * display in the autocomplete box.
     *
     * @param string $query The user query
     *
     * @return array        The suggestions for the provided query
     * @access public
     */
    public function getSuggestions($query)
    {
        // Initialize return array:
        $results = array();

        // Build target URL:
        $target = $this->url . '?input=' . urlencode($query);

        // Retrieve and parse response:
        $tmp = file_get_contents($target);
        if ($tmp && ($json = json_decode($tmp)) && isset($json->result)
            && is_array($json->result)
        ) {
            foreach ($json->result as $current) {
                if (isset($current->term)) {
                    $results[] = $current->term;
                }
            }
        }

        // Send back results:
        return array_unique($results);
    }
}

?>
