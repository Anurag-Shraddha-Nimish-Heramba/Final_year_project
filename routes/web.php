<?php

use App\Models\Organization;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/user/issueinfo', function () {
    return view('user/issueinfo', ['id' => $_GET['id']]);
});

Route::get('/login', function () {
    return view('login');
});
Route::get('/createorganization', function () {
    return view('createorganization');
});

Route::get('/organization/dashboard', function () {
    return view('organization/dashboard');
});

Route::get('/organization/charts', function () {
    $organization_id = \Illuminate\Support\Facades\Auth::guard("organization")->user()->id;

    $issue_response = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/organizationIssues/' . $organization_id);
    $issues = $issue_response["data"];

    $tagid = array();
    $tagname = array();

    $statusid = array();
    $statusname = array();

    $authorid = array();
    $authorname = array();
    foreach ($issues as $issue) {
        foreach ($issue['tags'] as $tag) {
            if (array_key_exists($tag['id'], $tagid)){
                $tagid[$tag['id']] = $tagid[$tag['id']] + 1;
            }
            else {
                $tagid[$tag['id']] = 1;
                array_push($tagname, $tag['name']);
            }
        }
        if (array_key_exists($issue['status']['id'], $statusid)){
            $statusid[$issue['status']['id']] = $statusid[$issue['status']['id']] + 1;
        }
        else {
            $statusid[$issue['status']['id']] = 1;
            array_push($statusname, $issue['status']['name']);
        }

        if (array_key_exists($issue['author']['id'], $authorid)){
            $authorid[$issue['author']['id']] = $authorid[$issue['author']['id']] + 1;
        }
        else {
            $authorid[$issue['author']['id']] = 1;
            array_push($authorname, $issue['author']['name']);
        }
    }

    return view('organization/charts', ['tagid' => $tagid,
                                        'tagname' => $tagname,
                                        'statusid' => $statusid,
                                        'statusname' => $statusname,
                                        'authorid' => $authorid,
                                        'authorname' => $authorname,
                                        ]);
});

Route::get('/organization/issues', function () {
    return view('organization/issues');
});

Route::get('/organization/employee', function () {
    return view('organization/employee');
});

Route::get('/organization/editorgprofile', function () {
    return view('organization/editorgprofile');
});

Route::get('/organization/profile', function () {
    return view('organization/profile');
});
Route::get('/user/dashboard', function () {
    return view('user/dashboard');
});
Route::get('/user/issues', function () {
    return view('user/issues', ['status' => '']);
});
Route::post('/user/issues', function () {
    if(isset($_POST['addissue'])){
        $title = $_POST['title'];
        $org_id = $_POST['organization_id'];
        $desc_comment = $_POST['description'];
        $tag_list = $_POST['dropdown-group'];
        $tag_array = array();

        foreach ($tag_list as $tag){
            array_push($tag_array, $tag);
        }

        $response = \Illuminate\Support\Facades\Http::post('http://localhost:8001/api/issue/', [
            'author_id' => \Illuminate\Support\Facades\Auth::user()->id,
            'title' => $title,
            'desc_comment_id' => \Illuminate\Support\Facades\Http::post('http://localhost:8001/api/comment/', [
                'user_id' => \Illuminate\Support\Facades\Auth::user()->id,
                'contentt' => $desc_comment,
                'upvote' => 0,
                'downvote' => 0,
            ])["data"]["id"],
            'assignee_id' => 1,
            'status_id' => 1,
            'tags' => $tag_array,
            'organization_id' => $org_id,
        ]);

    }
    return view('user/issues', ['status' => $response]);
});
Route::get('/user/organization', function () {
    $user_id = \Illuminate\Support\Facades\Auth::user()->id;
    $user_response = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/user/' . $user_id);
    $user = $user_response["data"];

    $issue_response = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/userIssues/' . $user_id);
    $issues = $issue_response["data"];

    $orgs = $user["organizations"];
    return view('user/organization', ['user' => $user,
                                        'issues' => $issues,
                                        'orgs' => $orgs,
                                        'status' => '']);
});
Route::post('/user/organization', function () {
    $user_id = \Illuminate\Support\Facades\Auth::user()->id;
    $user_response = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/user/' . $user_id);
    $user = $user_response["data"];

    $issue_response = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/userIssues/' . $user_id);
    $issues = $issue_response["data"];

    $orgs = $user["organizations"];
    if(isset($_POST['searchorg'])){
        $org_name = $_POST['organization_name'];
        $organizations = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/organization/' . explode(' ', $org_name)[0])->collect();
        if(count($organizations)>0){
            $stat = 'Organization returned';
            return view('user/organization', ['status' => $stat,
                                                'user' => $user,
                                                'issues' => $issues,
                                                'orgs' => $orgs,
                                                'neworgs' => $organizations['data']]);
        }
        else {
            $stat = 'Organization does not exists';
            return view('user/organization', ['status' => $stat,
                                                'user' => $user,
                                                'issues' => $issues,
                                                'orgs' => $orgs,
                                                'neworgs' => null]);
        }
    }
});

Route::post('/user/issue/{id}/comment', function ($id) {
    $user_id = \Illuminate\Support\Facades\Auth::user()->id;
    $content = $_POST['content'];
    
    // Get the issue to find the desc_comment_id (parent comment)
    $issue_response = \Illuminate\Support\Facades\Http::get('http://localhost:8001/api/issue/' . $id);
    $issue = $issue_response['data'];
    
    // Use provided parent_id if replying to a specific comment, otherwise use issue's main comment
    $parent_id = $_POST['parent_id'] ?? $issue['desc_comment']['id'];
    
    // Create comment via API
    $response = \Illuminate\Support\Facades\Http::post('http://localhost:8001/api/comment/', [
        'user_id' => $user_id,
        'contentt' => $content,  // Note: API uses 'contentt' not 'content'
        'upvote' => 0,
        'downvote' => 0,
        'parent_id' => $parent_id,
    ]);
    
    return redirect('/user/issueinfo?id=' . $id);
});

Route::post('login', 'App\Http\Controllers\LoginController@login')->name('login');

Route::get('logout', 'App\Http\Controllers\LoginController@logout')->name('logout');
