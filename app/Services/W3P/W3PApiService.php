<?php

namespace App\Services\W3P;

use App\Export\MNSR\PosBgcDataExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use SoapClient;
use SoapFault;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class W3PApiService
{
    private $creds;

    /**
     * Initialize credentials based on provided branch:
     * 'luzon', 'visayas', or 'mindanao'
     */
    public function __construct($branch)
    {
        $branch = strtolower($branch);
        switch ($branch) {
            case 'visayas':
                $this->creds = '<id><fw3p_id>22080383</fw3p_id><fw3p_key>12345</fw3p_key></id>';
                break;
            case 'mindanao':
                $this->creds = '<id><fw3p_id>22081686</fw3p_id><fw3p_key>12345</fw3p_key></id>';
                break;
            case 'luzon':
            default:
                $this->creds = '<id><fw3p_id>22081685</fw3p_id><fw3p_key>12345</fw3p_key></id>';
                break;
        }
    }

    /**
     * Initialize SoapClient from API Server
     *
     * @return SoapClient
     *
     * @throws SoapFault
     */
    public function getClient()
    {
        ini_set('soap.wsdl_cache_enabled', '0');

        $client = new SoapClient('http://app2.alliancewebpos.com/appserv/app/w3p/w3p.wsdl',
            ['location' => 'http://app2.alliancewebpos.com/appserv/app/w3p/W3PSoapServer.php']);

        return $client;
    }

    /**
     * Call API Action (refer to documentation for all actions)
     *
     * @return mixed|void
     *
     * @throws SoapFault
     */
    public function callAction($action, $data = null)
    {
        $params = "<root>{$this->creds}{$data}</root>";

        $client = $this->getClient();
        $response = $client->call($action, $params);

        // fix encoding issues
        // $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');

        // remove unnecessary whitespace (tabs, newlines, carriage returns)
        $response = str_replace(["\t", "\n", "\r"], '', $response);

        $replace_entities = [
            '&Ntilde;' => 'Ñ',
            '&ntilde;' => 'ñ',
            '&Aacute;' => 'Á',
            '&aacute;' => 'á',
            '&Eacute;' => 'É',
            '&eacute;' => 'é',
            '&Iacute;' => 'Í',
            '&iacute;' => 'í',
            '&Oacute;' => 'Ó',
            '&oacute;' => 'ó',
            '&Uacute;' => 'Ú',
            '&uacute;' => 'ú',
        ];

        // replace invalid named entities with UTF-8 characters
        $response = str_replace(array_keys($replace_entities), array_values($replace_entities), $response);

        // named entities with numeric equivalents
        $response = preg_replace('/&([a-zA-Z]+);/', '', $response); // Removes unknown named entities

        // convert named entities to valid XML format
        $response = html_entity_decode($response, ENT_XML1, 'UTF-8');

        // load XML string
        $xmlObject = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xmlObject === false) {
            exit('Error parsing XML');
        }

        // convert XML object to JSON
        $jsonString = json_encode($xmlObject);

        // return converted JSON as array object
        return json_decode($jsonString, true);
    }

    /**
     * Generate BGC Data (must initialize service (__construct) to prepare credentials for specified branch)
     *
     *
     * @return BinaryFileResponse
     */
    public function generateBgcData($month, $year)
    {
        $branches = $this->getBranches();
        $products = $this->getProducts();
        $sales = $this->getSalesOfMonth($month, $year);

        $response = [];
        foreach ($sales as $salesDateIndex => $salesDate) { // sales data grouped by date
            foreach ($branches as $branch) { // all branches
                foreach ($salesDate['sales'] as $salesOfDate) { // all sales in date
                    if ($salesOfDate['data']['record']['sales']['fofficeid'] == $branch['branch_code']) {
                        $row = [];

                        $row['date'] = Carbon::createFromFormat('Ymd', $salesDateIndex)->format('m/d/Y');
                        $row['branch_code'] = $branch['branch_code'];
                        $row['branch_name'] = $branch['branch_name'];
                        // $row['total'] = $salesOfDate['data']['record']['sales']['fgross'];

                        $bread_total = 0;
                        $ice_cream_total = 0;
                        $softdrinks_total = 0;
                        $others_total = 0;

                        foreach ($salesOfDate['data']['record']['sales']['product'] as $productSold) {
                            foreach ($products as $product) {
                                // fallback implementation as some sales data only returns single entry on product
                                $single_product_sold = false;
                                if (! isset($productSold['fproductid'])) {
                                    $single_product_sold = true;
                                    $productSold = json_decode(json_encode($salesOfDate['data']['record']['sales']['product']), true);
                                }

                                $productId = $product['fproductid'];
                                $soldProductId = $productSold['fproductid'];

                                if ($productId == $soldProductId) {
                                    $product_category = $product['fcategory_value6'];
                                    switch ($product_category) {
                                        case 'BREAD':
                                            $bread_total += $productSold['ftotal_line'];
                                            break;
                                        case 'ICECREAM':
                                            $ice_cream_total += $productSold['ftotal_line'];
                                            break;
                                        case 'SOFTDRINKS':
                                            $softdrinks_total += $productSold['ftotal_line'];
                                            break;
                                        case 'OTHERS':
                                            $others_total += $productSold['ftotal_line'];
                                            break;
                                    }

                                    if ($single_product_sold) {
                                        // prevent iterating if detected as single product
                                        break;
                                    }
                                }
                            }
                        }

                        // TODO :: remove on deployment -- used only for cross checking sales total vs products total
                        // $row['products_total'] = ($bread_total + $ice_cream_total + $softdrinks_total + $others_total);

                        $row['bread_total'] = $bread_total;
                        $row['ice_cream_total'] = $ice_cream_total;
                        $row['softdrinks_total'] = $softdrinks_total;
                        $row['others_total'] = $others_total;

                        // $response[$row['date']][] = $row;
                        $response[] = $row;
                    }
                }
            }
        }

        //        NOTE :: Detected 2 unused branches
        //        B23466 - C. TIRONA, POBLACION, BATANGAS CITY
        //        B25155 - WALTERMART PANIQUI, TARLAC
        //
        //
        //        $usedBranches = [];
        //        foreach ($response as $date) {
        //            foreach ($date as $row) {
        //                if (!in_array($row['branch_code'], $usedBranches)) {
        //                    $usedBranches[] = $row['branch_code'];
        //                }
        //            }
        //        }
        //
        //        $unusedBranches = array_filter($branches, function ($branch) use ($usedBranches) {
        //            return !in_array($branch['branch_code'], $usedBranches);
        //        });
        //
        //        return $unusedBranches;

        // sort the response by branch code and then by date
        usort($response, function ($a, $b) {
            if ($a['branch_code'] == $b['branch_code']) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            }

            return $a['branch_code'] <=> $b['branch_code'];
        });

        return Excel::download(new PosBgcDataExport(collect($response)), 'bgc.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        // return $response;
    }

    /**
     * Returns all branches in array format (branch_code, branch_name)
     *
     * @return array
     */
    public function getBranches()
    {
        // TODO :: remove on deployment
        // NOTES :: used for caching data only, to reduce waiting time when debugging
        if (config('app.env') == 'local') {
            if (Storage::exists('branches-api-data.txt')) {
                return json_decode(Storage::get('branches-api-data.txt'), true);
            }
        }

        $branchesApiData = $this->callAction('GET_OFFICE', null);

        $branches = [];
        foreach ($branchesApiData['data']['record'] as $branchApiData) {
            $branches[] = [
                'branch_code' => $branchApiData['fofficeid'],
                'branch_name' => $branchApiData['fname'],
            ];
        }

        // sort by branch_code
        usort($branches, function ($item1, $item2) {
            return $item1['branch_code'] <=> $item2['branch_code'];
        });

        // TODO :: remove on deployment
        // NOTES :: used for caching data only, to reduce waiting time when debugging
        if (config('app.env') == 'local') {
            Storage::put('branches-api-data.txt', json_encode($branches));
        }

        return $branches;
    }

    /**
     * Returns sales data across all branches throughout the selected month and year
     *
     * @param  $month  int
     * @param  $year  int
     * @return array
     */
    public function getSalesOfMonth($month, $year)
    {
        $start_date = Carbon::createFromDate($year, $month, 1)->setTime(0, 0, 0, 0);
        $diff_in_days = $start_date->clone()->endOfMonth()->setTime(0, 0, 0, 0)->diffInDays($start_date, true);

        // TODO :: remove on deployment
        // NOTES :: used for caching data only, to reduce waiting time when debugging
        if (config('app.env') == 'local') {
            if (Storage::exists('sales-api-data-'.$start_date->format('Ym').'.txt')) {
                return json_decode(Storage::get('sales-api-data-'.$start_date->format('Ym').'.txt'), true);
            }
        }

        $response = [];
        $lastSalesData = null;

        for ($i = 0; $i < (int) $diff_in_days; $i++) { // TODO :: change $i < 1 to $i < $diff_in_days
            $date = $start_date->clone()->addDays($i)->format('Ymd');

            $sales = [];
            $count = 0;
            do {
                if ($count == 0) {
                    $salesFilter = $this->getSalesFilter($date);
                    $salesData = $this->callAction('GET_SALES', $salesFilter);
                } else {
                    $salesFilter = $this->getSalesFilter($date,
                        $lastSalesData['data']['fnew_batchid'],
                        $lastSalesData['data']['flast_batchid'],
                        $lastSalesData['data']['flast_key']);
                    $salesData = $this->callAction('GET_SALES', $salesFilter);
                }

                $lastSalesData = $salesData;

                if ($salesData['data']['record']['fsale_date'] == $date
                    && isset($salesData['data']['record']['sales']['product'])) {
                    $sales[] = $salesData;
                }

                $count++;
            } while ($lastSalesData['data']['record']['fsale_date'] == $date);

            $response[$date]['sales'] = $sales;
        }

        // TODO :: remove on deployment
        // NOTES :: used for caching data only, to reduce waiting time when debugging
        if (config('app.env') == 'local') {
            Storage::put('sales-api-data-'.$start_date->format('Ym').'.txt', json_encode($response));
        }

        return $response;
    }

    /**
     * @param  $date  string Date Filter in Ymd format
     * @param  $new_batch_id  string Data from initial or last API call
     * @param  $last_batch_id  string Data from initial or last API call
     * @param  $last_key  string Data from initial or last API call
     * @return string
     */
    private function getSalesFilter($date, $new_batch_id = null, $last_batch_id = null, $last_key = null)
    {
        $filterString = "<ffrom>$date</ffrom>";

        if ($new_batch_id != null) {
            $filterString .= "<fnew_batchid>$new_batch_id</fnew_batchid>";
        }

        if ($last_batch_id != null) {
            $filterString .= "<flast_batchid>$last_batch_id</flast_batchid>";
        }

        if ($last_key != null) {
            $filterString .= "<flast_key>$last_key</flast_key>";
        }

        return
            "<data>
                <filter>
                $filterString
                <fper_customer_flag>0</fper_customer_flag>
                </filter>
            </data>";
    }

    /**
     * Returns raw response of specific product data from API
     * NOTE :: CURRENTLY UNUSED
     *
     * @param  $product_id  string Product ID (fproductid)
     * @return mixed
     */
    public function getProduct($product_id)
    {
        $productFilter = "<data>
                <filter>
                <fproductid>$product_id</fproductid>
                <fthirdpartyid></fthirdpartyid>
                <fkeyword></fkeyword>
                <fcompress>1</fcompress>
                </filter>
                </data>";

        return $this->callAction('GET_PRODUCT', $productFilter);
    }

    /**
     * Returns list of all products
     *
     * @return array
     */
    public function getProducts()
    {
        // TODO :: remove on deployment
        // NOTES :: used for caching data only, to reduce waiting time when debugging
        if (config('app.env') == 'local') {
            if (Storage::exists('products-api-data.txt')) {
                return json_decode(Storage::get('products-api-data.txt'), true);
            }
        }

        $response = [];

        $lastProductData = null;
        $count = 0;
        do {
            if ($count == 0) {
                $productsFilter = $this->getProductsFilter(null, null, null);
                $productsData = $this->callAction('GET_PRODUCT', $productsFilter);
            } else {
                $productsFilter = $this->getProductsFilter($lastProductData['data']['fnew_batchid'],
                    $lastProductData['data']['flast_batchid'],
                    $lastProductData['data']['flast_key']);
                $productsData = $this->callAction('GET_PRODUCT', $productsFilter);
            }

            if (isset($productsData['data']['record'])) {
                $response = array_merge($response, $productsData['data']['record']);
            }

            $lastProductData = $productsData;
            $count++;
        } while ($lastProductData['data']['fdone'] == 0);

        // TODO :: remove on deployment
        // NOTES :: used for caching data only, to reduce waiting time when debugging
        if (config('app.env') == 'local') {
            Storage::put('products-api-data.txt', json_encode($response));
        }

        return $response;
    }

    /**
     * @param  $new_batch_id  string Data from initial or last API call
     * @param  $last_batch_id  string Data from initial or last API call
     * @param  $last_key  string Data from initial or last API call
     * @return string
     */
    private function getProductsFilter($new_batch_id = null, $last_batch_id = null, $last_key = null)
    {
        $filterString = '';

        if ($new_batch_id != null) {
            $filterString .= "<fnew_batchid>$new_batch_id</fnew_batchid>";
        }

        if ($last_batch_id != null) {
            $filterString .= "<flast_batchid>$last_batch_id</flast_batchid>";
        }

        if ($last_key != null) {
            $filterString .= "<flast_key>$last_key</flast_key>";
        }

        return
            "<data>
                <filter>
                $filterString
                <fper_customer_flag>0</fper_customer_flag>
                </filter>
            </data>";
    }
}
