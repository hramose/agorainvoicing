<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Model\Front\FrontendPage;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public $page;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');

        $page = new FrontendPage();
        $this->page = $page;
    }

    public function index()
    {
        try {
            return view('themes.default1.front.page.index');
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function getPages()
    {
        return \DataTables::of($this->page->get())
                        ->addColumn('checkbox', function ($model) {
                            return "<input type='checkbox' class='page_checkbox' value=".$model->id.' name=select[] id=check>';
                        })
                        ->addColumn('name', function ($model) {
                            return ucfirst($model->name);
                        })
                        ->addColumn('url', function ($model) {
                            return $model->url;
                        })
                        ->addColumn('created_at', function ($model) {
                            return $model->created_at;
                        })

                        ->addColumn('content', function ($model) {
                            return str_limit($model->content, 10, '...');
                        })
                        ->addColumn('action', function ($model) {
                            return '<a href='.url('pages/'.$model->id.'/edit')." class='btn btn-sm btn-primary'>Edit</a>";
                        })

                          ->rawColumns(['checkbox', 'name', 'url',  'created_at', 'content', 'action'])
                        ->make(true);
        // ->searchColumns('name', 'content')
                        // ->orderColumns('name')
                        // ->make();
    }

    public function create()
    {
        try {
            $parents = $this->page->pluck('name', 'id')->toArray();

            return view('themes.default1.front.page.create', compact('parents'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $page = $this->page->where('id', $id)->first();
            $parents = $this->page->where('id', '!=', $id)->pluck('name', 'id')->toArray();

            return view('themes.default1.front.page.edit', compact('parents', 'page'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required',
            'publish' => 'required',
            'slug'    => 'required',
            'url'     => 'required',
            'content' => 'required',
        ]);

        try {
            $this->page->fill($request->input())->save();

            return redirect()->back()->with('success', \Lang::get('message.saved-successfully'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function update($id, Request $request)
    {
        $this->validate($request, [
            'name'    => 'required',
            'publish' => 'required',
            'slug'    => 'required',
            'url'     => 'required',
            'content' => 'required',
        ]);

        try {
            $page = $this->page->where('id', $id)->first();
            $page->fill($request->input())->save();

            return redirect()->back()->with('success', \Lang::get('message.updated-successfully'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function getPageUrl($slug)
    {
        $productController = new \App\Http\Controllers\Product\ProductController();
        $url = $productController->GetMyUrl();
        $segment = $this->addSegment(['public/pages']);
        $url = $url.$segment;

        $slug = str_slug($slug, '-');
        echo $url.'/'.$slug;
    }

    public function getSlug($slug)
    {
        $slug = str_slug($slug, '-');
        echo $slug;
    }

    public function addSegment($segments = [])
    {
        $segment = '';
        foreach ($segments as $seg) {
            $segment .= '/'.$seg;
        }

        return $segment;
    }

    public function generate(Request $request)
    {
        // dd($request->all());
        if ($request->has('slug')) {
            $slug = $request->input('slug');

            return $this->getSlug($slug);
        }
        if ($request->has('url')) {
            $slug = $request->input('url');

            return $this->getPageUrl($slug);
        }
    }

    public function show($slug)
    {
        try {
            $page = $this->page->where('slug', $slug)->where('publish', 1)->first();
            if ($page->type == 'cart') {
                return $this->cart();
            }

            return view('themes.default1.front.page.show', compact('page'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            $ids = $request->input('select');
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $page = $this->page->where('id', $id)->first();
                    if ($page) {
                        // dd($page);
                        $page->delete();
                    } else {
                        echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>".\Lang::get('message.alert').'!</b> '.\Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        '.\Lang::get('message.no-record').'
                </div>';
                        //echo \Lang::get('message.no-record') . '  [id=>' . $id . ']';
                    }
                }
                echo "<div class='alert alert-success alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>".\Lang::get('message.alert').'!</b> '.\Lang::get('message.success').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        '.\Lang::get('message.deleted-successfully').'
                </div>';
            } else {
                echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>".\Lang::get('message.alert').'!</b> '.\Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        '.\Lang::get('message.select-a-row').'
                </div>';
                //echo \Lang::get('message.select-a-row');
            }
        } catch (\Exception $e) {
            echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>".\Lang::get('message.alert').'!</b> '.\Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        '.$e->getMessage().'
                </div>';
        }
    }

    public function search(Request $request)
    {
        try {
            $search = $request->input('q');
            $model = $this->result($search, $this->page);

            return view('themes.default1.front.page.search', compact('model'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function result($search, $model)
    {
        try {
            $model = $model->where('name', 'like', '%'.$search.'%')->orWhere('content', 'like', '%'.$search.'%')->paginate(10);

            return $model->setPath('search');
        } catch (\Exception $ex) {
            //dd($ex);
            throw new \Exception('Can not get the search result');
        }
    }

    public function transform($type, $data, $trasform = [])
    {
        $config = \Config::get("transform.$type");
        $result = '';
        $array = [];
        foreach ($trasform as $trans) {
            $array[] = $this->checkConfigKey($config, $trans);
        }
        for ($i = 0; $i < count($array); $i++) {
            $array1 = $this->keyArray($array[$i]);
            $array2 = $this->valueArray($array[$i]);
            $result .= str_replace($array1, $array2, $data);
        }

        return $result;
    }

    public function checkString($data, $string)
    {
        if (strpos($data, $string) !== false) {
            return true;
        }
    }

    public function cart()
    {
        // $location = \GeoIP::getLocation();
        //       $location = ['ip'   => '::1',
        // 'isoCode'                 => 'IN',
        // 'country'                 => 'India',
        // 'city'                    => 'Bengaluru',
        // 'state'                   => 'KA',
        // 'postal_code'             => 560076,
        // 'lat'                     => 12.9833,
        // 'lon'                     => 77.5833,
        // 'timezone'                => 'Asia/Kolkata',
        // 'continent'               => 'AS',
        // 'default'                 => false, ];
          if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
          $ip = $_SERVER['HTTP_CLIENT_IP'];
          } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
              $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
          } else {
              $ip = $_SERVER['REMOTE_ADDR'];
          }

        if ($ip != '::1') {
            $location = json_decode(file_get_contents('http://ip-api.com/json/'.$ip), true);
        } else {
            $location = json_decode(file_get_contents('http://ip-api.com/json'), true);
        }
        // $location = json_decode(file_get_contents('http://ip-api.com/json'), true);

        $country = \App\Http\Controllers\Front\CartController::findCountryByGeoip($location['countryCode']);
        $states = \App\Http\Controllers\Front\CartController::findStateByRegionId($location['countryCode']);
        $states = \App\Model\Common\State::pluck('state_subdivision_name', 'state_subdivision_code')->toArray();
        $state_code = $location['countryCode'].'-'.$location['region'];
        $state = \App\Http\Controllers\Front\CartController::getStateByCode($state_code);
        $mobile_code = \App\Http\Controllers\Front\CartController::getMobileCodeByIso($location['countryCode']);

        if ($location['country'] == 'India') {
            $currency = 'INR';
        } else {
            $currency = 'USD';
        }
        if (\Auth::user()) {
            $currency = 'INR';
            $user_currency = \Auth::user()->currency;
            if ($user_currency == 1 || $user_currency == 'USD') {
                $currency = 'USD';
            }
        }
        \Session::put('currency', $currency);
        if (!\Session::has('currency')) {
            \Session::put('currency', 'INR');
        }
        $pages = $this->page->find(1);
        $data = $pages->content;

        $product = new \App\Model\Product\Product();
        $helpdesk_products = $product->where('id', '!=', 1)->where('category', '=', 'helpdesk')->get()->toArray();

        //$cart_controller = new \App\Http\Controllers\Front\CartController();
        $temp_controller = new \App\Http\Controllers\Common\TemplateController();
        // dd($temp_controller);
        $trasform = [];
        $template = '';

        if (count($helpdesk_products) > 0) {
            foreach ($helpdesk_products as $key => $value) {
                $trasform[$value['id']]['price'] = $temp_controller->leastAmount($value['id']);
                $trasform[$value['id']]['name'] = $value['name'];
                $trasform[$value['id']]['feature'] = $value['description'];
                $trasform[$value['id']]['subscription'] = $temp_controller->plans($value['shoping_cart_link'], $value['id']);
                // dd($temp_controller->leastAmount($value['id']), $temp_controller->plans($value['shoping_cart_link'], $value['id']));
                $trasform[$value['id']]['url'] = "<input type='submit' value='Buy' class='btn btn-primary'></form>";
            }
            $template = $this->transform('cart', $data, $trasform);
            // dd($template);
        }

        $sevice_desk_products = $product->where('id', '!=', 1)->where('category', '=', 'servicedesk')->get()->toArray();

        $servicedesk_template = '';
        $trasform1 = [];
        if (count($sevice_desk_products) > 0) {
            foreach ($sevice_desk_products as $key => $value) {
                $trasform1[$value['id']]['price'] = $temp_controller->leastAmount($value['id']);
                $trasform1[$value['id']]['name'] = $value['name'];
                $trasform1[$value['id']]['feature'] = $value['description'];
                $trasform1[$value['id']]['subscription'] = $temp_controller->plans($value['shoping_cart_link'], $value['id']);

                $trasform1[$value['id']]['url'] = "<input type='submit' value='Buy' class='btn btn-primary'></form>";
            }
            $servicedesk_template = $this->transform('cart', $data, $trasform1);
        }

        $service = $product->where('id', '!=', 1)->where('category', '=', 'service')->get()->toArray();
        $service_template = '';
        $trasform2 = [];
        if (count($service) > 0) {
            foreach ($service as $key => $value) {
                $trasform2[$value['id']]['price'] = $temp_controller->leastAmountService($value['id']);
                $trasform2[$value['id']]['name'] = $value['name'];
                $trasform2[$value['id']]['feature'] = $value['description'];

                // $trasform2[$value['id']]['subscription'] = $temp_controller->leastAmountService($value['id']);
                $trasform2[$value['id']]['subscription'] = $temp_controller->plans($value['shoping_cart_link'], $value['id']);

                $trasform2[$value['id']]['url'] = "<input type='submit' value='Buy' class='btn btn-primary'></form>";
            }
            $service_template = $this->transform('cart', $data, $trasform2);
        }

        return view('themes.default1.common.template.shoppingcart', compact('template', 'trasform', 'servicedesk_template', 'trasform1', 'service_template', 'trasform2'));
    }

    public function checkConfigKey($config, $transform)
    {
        $result = [];
        //        dd($config);
        if (count($config) > 0) {
            foreach ($config as $key => $value) {
                if (array_key_exists($key, $transform)) {
                    $result[$value] = $transform[$key];
                }
            }
        }

        return $result;
    }

    public function keyArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = $key;
        }

        return $result;
    }

    public function valueArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = $value;
        }

        return $result;
    }
}
