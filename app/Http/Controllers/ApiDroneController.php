<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\Flycam;
use App\models\Guarantees;
use App\models\History;
use App\models\History_maps;
use App\models\Image_Image_url;
use App\models\Specification_parameters;
use App\models\Specifications;
use DB;
use App\APIReturnHelper;
use Carbon\Carbon;
use Laravel\Passport\Client as Oclient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Exists;

class ApiDroneController extends Controller
{
    public $successStatus = 200;
    public function uploadImage(Request $request){
        // Thiết lập required cho cả 2 mục input
        $this->validate($request,[
            'image' =>'required',
            'fid' =>'required',
            'type' => 'required',
        ]);
        // kiểm tra có files sẽ xử lý
        if($request->hasFile('image')) {
            $allowedfileExtension=['jpg','png'];
            $files = $request->file('image');
            // flag xem có thực hiện lưu DB không. Mặc định là có
            $exe_flg = true;
            // kiểm tra tất cả các files xem có đuôi mở rộng đúng không
            foreach($files as $file) {
                $extension = $file->getClientOriginalExtension();
                $check=in_array($extension,$allowedfileExtension);
                if(!$check) {
                    // nếu có file nào không đúng đuôi mở rộng thì đổi flag thành false
                    $exe_flg = false;
                    break;
                }
            }
            if($request->type == 0 && $exe_flg){
                $time_now = Carbon::now()->isoFormat('YYYYMDhmmss');
                //Chỉ lấy ảnh đầu tiên
                $photo = $request->image[0];
                $user = Auth::user();
                //Check có user không nhưng để tránh không có quyền mà upload dữ liệu lên sẽ bị hack
                // if(($user->avatar)){
                $avatar_store_older = DB::table('image_url')->where("url", $user->avatar)->value('store');
                if(($avatar_store_older)){
                    // dd($avatar_store_older);
                    Storage::delete($avatar_store_older);
                    DB::table('image_url')->where("url", $user->avatar)->delete();
                }
                $name_image = 'image'.rand().$time_now  . '.' .$photo->getClientOriginalExtension();
                $stores = $photo->storeAs('public/photos', $name_image );
                $url = '/storage/photos/'.$name_image;
                $create_image_url['url'] = $url;
                $create_image_url['image_url_id_user'] = $user->id;
                $create_image_url['store'] = $stores;
                $image_url = Image_Image_url::create($create_image_url);
                
                DB::table('users')->where('id',$user->id)->update(['avatar' => $create_image_url['url']]);
                return response()->json([
                    'message' => "success",
                    'url'     => $create_image_url['url'],
                    'type'    => 'avatar',
                    'user_add'    => $user->name,
                ],200);
                
            // };

            }
            elseif($request->type == 1 && $exe_flg){
                // Thêm  Images cho flycam 

                // $create_flycam['fid']="3";
                // $create_flycam['model'] = "Model632";
                // $create_flycam['owner_id'] = Auth::user()->id;
                // Flycam::create($create_flycam);
                // dd("okok");
                $fid = DB::table('fcam')->where('fid',$request->fid);
                if($fid->exists()){
                }
                $arr_url_image =[];

                if(is_null($fid->first())){
                    return response()->json([
                        "error" => " fid not found",
                    ], 404 );
                }
                if($fid->first()->images){
                    $arr_url_image = array_merge($arr_url_image,json_decode($fid->first()->images));
                }
                foreach ($request->image as $photo) {
                    $time_now = Carbon::now()->isoFormat('YYYYMDhmmss');
                    $name_image = $fid->first()->model.rand().$time_now  . '.' .$photo->getClientOriginalExtension();
                    $url = '/storage/photos/'.$name_image;
                    $stores = $photo->storeAs('public/photos',$name_image);
                    $url = array($url);
                    $arr_url_image = array_merge($arr_url_image,$url);
                }
                DB::table('fcam')->where('fid',$request->fid)->update(['images'=>json_encode($arr_url_image)]);
                return response()->json([
                    'message' => "success",
                    'url'     => $arr_url_image,
                    'type'    => 'flycam',
                    'flycamID'    => $fid->first()->fid,
                ],200);
            }
            else {
                echo "Falied to upload. Only accept jpg, png photos.";
            }

        }
    }
    public function getFlycamByFID(Request $request){
        //Lấy flycam bằng fid
        $validator = Validator::make($request->all(),[
            'fid'=>'required',
        ],[
            'fid.required'=>'fid không được để trống',
        ]);

    if ($validator->fails()) { 
        return response()->json(['error'=>$validator->errors()], 401);            
    }

        $fcam = DB::table('fcam')->where('fid',$request->fid)->first();
        if(is_null($fcam)){
            return response()->json([
                "error" => "fid not found"
            ], 404);
        }
        // return response()->json([
        //     "fid"               => $fcam->fid,
        //     "owner_id"          => $fcam->owner_id,
        //     "model"             => $fcam->model,
        //     "camera"            => $fcam->camera,
        //     "maximum_altitude"  => $fcam->max_al,
        //     "maximum_range"     => $fcam->max_range,
        //     "pin"               => $fcam->pin,
        //     "images"            => json_decode($fcam->images),
        //     "guarantee"         => $fcam->guarantees,
        //     "specifications"    =>$fcam->specifications,

        // ], $this->successStatus);
         return response()->json([
            "fid"               => $fcam->fid,
            "owner_id"          => $fcam->owner_id,
            "model"             => $fcam->model,
            "camera"            => $fcam->camera,
            "maximum_altitude"  => $fcam->maximum_altitude,
            "maximum_range"     => $fcam->maximum_range,
            "pin"               => $fcam->pin,
            "images"            => json_decode($fcam->images),
            "guarantee"         => json_decode($fcam->guarantee),
            "specifications"    =>json_decode($fcam->specifications),
            "history"    =>json_decode($fcam->history),

        ], $this->successStatus);
    }
    
    public function getFlycamByOwenerID(Request $request){
        //Lấy flycam bằng id chủ sở hữu
        $validator = Validator::make($request->all(),[
            'id'=>'required',
        ],[
            'id.required'=>'id không được để trống',
        ]);
    
        if ($validator->fails()) { 
        return response()->json(['error'=>$validator->errors()], 401);            
        }
        $fcam = DB::table('fcam')->where('owner_id',DB::table('users')->where('id',$request->id)->first()->id)->get();
        if(is_null($fcam)){
            return response()->json([
                "error" => "Không sở hữu flycam nào"
            ], 404);
        }
        return response()->json($fcam, $this->successStatus);
    }
    public function creatFlycamNew(Request $request){
        //Tạo mới flycam
        $fcam = $request->all();
        $validator = Validator::make($fcam,[
            'onwner_id' =>'required',
            // 'model'=>'required',
            // 'camera'=>'required',
            // 'maximum_altitude'=>'required',
            // 'maximum_range'=>'required',
            // 'speed'=>'required',
            // 'pin'=>'required',
            // 'images'=>'required',
            // 'guarantee'=>'required',
            // 'specifications'=>'required',
            // 'history'=>'required',
        ],[
            'onwner_id.required'=>'onwner_id không được để trống',
            // 'model.required'=>'model không được để trống',
            // 'camera.required'=>'camera không được để trống',
            // 'maximum_altitude.required'=>'maximum_altitude không được để trống',
            // 'maximum_range.required'=>'maximum_range không được để trống',
            // 'speed.required'=>'speed không được để trống',
            // 'pin.required'=>'pin không được để trống',
            // 'images.required'=>'images không được để trống',
            // 'guarantee.required'=>'guarantee không được để trống',
            // 'specifications.required'=>'specifications không được để trống',
            // 'history.required'=>'history không được để trống',
        ]);
        if ($validator->fails()) { 
        return response()->json(['error'=>$validator->errors()], 401);            
        }
        $creat_fcam["owner_id"] = $fcam["onwner_id"];
        $creat_fcam["model"] = $fcam["model"];
        $creat_fcam["camera"] = $fcam["camera"];
        $creat_fcam["maximum_altitude"] = $fcam["maximum_altitude"];
        $creat_fcam["maximum_range"] = $fcam["maximum_range"];
        $creat_fcam["speed"] = $fcam["speed"];
        $creat_fcam["pin"] = $fcam["pin"];
        $creat_fcam["images"] = json_encode($fcam["images"]);
        $creat_fcam["guarantee"] = json_encode($fcam["guarantee"]);
        $creat_fcam["specifications"] = json_encode($fcam["specifications"]);
        
        $creat_fca = Flycam::create($creat_fcam); 
        return response()->json([
            "owner_id" => $creat_fca["owner_id"],
            "model" => $creat_fca["model"],
            "camera" => $creat_fca["camera"],
            "maximum_altitude" => $creat_fca["maximum_altitude"],
            "maximum_range" => $creat_fca["maximum_range"],
            "speed" => $creat_fca["speed"],
            "pin" => $creat_fca["pin"],
            "images" => json_decode($creat_fca["images"]),
            "guarantee" => json_decode($creat_fca["guarantee"]),
            "specifications" => json_decode($creat_fca["specifications"]),
        ], $this->successStatus);
    }
    public function updateFLycam(Request $request){
        //Update fly cam
        $fcam = $request->all();
        $validator = Validator::make($fcam,[
            'fid' =>'required',
            // 'model'=>'required',
            // 'camera'=>'required',
            // 'maximum_altitude'=>'required',
            // 'maximum_range'=>'required',
            // 'speed'=>'required',
            // 'pin'=>'required',
            // 'images'=>'required',
            // 'guarantee'=>'required',
            // 'specifications'=>'required',
            // 'history'=>'required',
        ],[
            'fid.required'=>'fid không được để trống',
            // 'model.required'=>'model không được để trống',
            // 'camera.required'=>'camera không được để trống',
            // 'maximum_altitude.required'=>'maximum_altitude không được để trống',
            // 'maximum_range.required'=>'maximum_range không được để trống',
            // 'speed.required'=>'speed không được để trống',
            // 'pin.required'=>'pin không được để trống',
            // 'images.required'=>'images không được để trống',
            // 'guarantee.required'=>'guarantee không được để trống',
            // 'specifications.required'=>'specifications không được để trống',
            // 'history.required'=>'history không được để trống',
        ]);
        if ($validator->fails()) { 
        return response()->json(['error'=>$validator->errors()], 401);            
        }
        
        $creat_fcam["fid"] = $fcam["fid"];
        
        $creat_fcam["model"] = $fcam["model"];
        $creat_fcam["camera"] = $fcam["camera"];
        $creat_fcam["maximum_altitude"] = $fcam["maximum_altitude"];
        $creat_fcam["maximum_range"] = $fcam["maximum_range"];
        $creat_fcam["speed"] = $fcam["speed"];
        $creat_fcam["pin"] = $fcam["pin"];
        if($request->exists('images')){
            $fcam["images"] = json_encode($fcam["images"]);
        }

        if($request->exists('images')){
            $fcam["guarantee"] = json_encode($fcam["guarantee"]);
        }
        if($request->exists('images')){
            $fcam["specifications"] = json_encode($fcam["specifications"]);
        }
        DB::table('fcam')->where('fid',$request->fid)->update($fcam);
        $fcam_1 = DB::table('fcam')->where('fid',$request->fid)->first();
        
        return response()->json([
            "fid" => $fcam_1->fid,
            "owner_id" => $fcam_1->owner_id,
            "model" => $fcam_1->model,
            "camera" => $fcam_1->camera,
            "maximum_altitude" => $fcam_1->maximum_altitude,
            "maximum_range" => $fcam_1->maximum_range,
            "speed" => $fcam_1->speed,
            "pin" => $fcam_1->pin,
            "images" => json_decode($fcam_1->images),
            "guarantee" => json_decode($fcam_1->guarantee),
            // "specifications" => json_decode($fcam_1->specifications),
        ], $this->successStatus);
        

    }

}
