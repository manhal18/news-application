<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index','getByCategoryId','show']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Article::with('categories')->orderBy('updated_at','DESC')->paginate(env('PAGE_LIMIT'));
    }

    public function getByCategoryId($category_id)
    {
        $articles = DB::table('articles')
                        ->join('category-article', 'articles.id', '=', 'category-article.article_id')
                        ->join('categories', 'categories.id', '=', 'category-article.category_id')
                        ->select('articles.id', 'articles.title','articles.description', 'articles.image_url', 'articles.updated_at', 'categories.name')
                        ->where('categories.id',$category_id)
                        ->orderBy('updated_at','DESC')
                        ->paginate(env('PAGE_LIMIT'));
        return response()->json($articles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->hasFile('image')){
            $currentDate = Carbon::now()->format('YmdHs');
            $img_name = $currentDate.' - '.$request->file('image')->getClientOriginalName();
            $path = $request->file('image')->move('images',$img_name);
            // ->storeAs('public/images',$img_name);
            $article = Article::create([
                'title' => $request->title,
                'description' => $request->description,
                'body' => $request->body,
                'image_url' => $img_name,
            ]);
            $article->categories()->sync($request->categories);
            $categoriesArray = Category::findMany($request->categories)->pluck('name')->toArray();
            $categoriesString = implode(" ",$categoriesArray);
            $tokens = Token::pluck('token')->toArray();
            $SERVER_API_KEY = 'AAAAbbsoCAE:APA91bEYNadWwelTC4_bLHMTMwi6hwI6PQyP1LHl0asagHpOoYkdx279mC7NQCR1MkSfJUTU6_ACZnDoKrB9gFvNlwBlqOBfF2wiq35AOId6zm5k7azpo_TinVMDXiKj7mKKex-t0qwu';
            $data = [
                "registration_ids" => $tokens,
                "notification" => [
                    "title" => $categoriesString,
                    "body" => $request->title,
                    "sound" => true
                ],
                "data" => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'article_id' => $article->id
                ]
            ];
            $dataString = json_encode($data);
            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            $response = curl_exec($ch);
            return response()->json(['article'=>$article,'categories'=>$categoriesString]);
        }else{
            return response()->json(['error'=>'The image field is required.'],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show($article)
    {
        $article_data = Article::find($article);
        $categories = DB::table('category-article')
                        ->select('category_id')
                        ->where('article_id',$article)
                        ->get();
        $categories_ids = [];
        foreach (json_decode($categories,true) as $item){
            array_push($categories_ids, $item['category_id']);
        }
        $categories_names = Category::findMany($categories_ids)->pluck('name')->toArray();
        return response()->json(['article'=>$article_data,'categories'=>$categories_names]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $old_image = Article::find($id, ['image_url'])['image_url'];
        // if(Storage::disk('local')->exists('public/images/'.$old_image)){
        //     Storage::delete('public/images/'.$old_image);
        // }
        if(File::exists(public_path('images/'.$old_image))){
            File::delete(public_path('images/'.$old_image));
        }

        if($request->hasFile('image')){
            $currentDate = Carbon::now()->format('YmdHs');
            $img_name = $currentDate.' - '.$request->file('image')->getClientOriginalName();
            $path = $request->file('image')->move('images',$img_name);
            // ->storeAs('public/images',$img_name);

            $article = Article::find($id);
            $article_data = [
                'title' => $request->title,
                'description' => $request->description,
                'body' => $request->body,
                'image_url' => $img_name,
            ];
            $article->update($article_data);

            DB::table('category-article')->where('article_id',$id)->delete();
            $article->categories()->sync($request->categories);

            return response()->json(true);
        }else{
            return response()->json(['error'=>'The image field is required.'],400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        $image_name = $article->image_url;
        // if(Storage::disk('local')->exists('public/images/'.$image_name)){
        //     Storage::delete('public/images/'.$image_name);
        // }
        if(File::exists(public_path('images/'.$image_name))){
            File::delete(public_path('images/'.$image_name));
        }

        $response = $article->delete();
        return response()->json($response);
    }
}
