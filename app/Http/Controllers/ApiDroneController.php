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
        return response()->json($fcam, $this->successStatus);
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
        

    }
    public function updateFLycam(Request $request){
        //Update fly cam


    }

}
