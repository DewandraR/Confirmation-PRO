<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class DetailController extends Controller
{ public function show(Request $req) { return view('detail', ['IV_AUFNR'=>$req->query('aufnr'), 'IV_PERNR'=>$req->query('pernr'), 'IV_ARBPL'=>$req->query('arbpl')]); } }