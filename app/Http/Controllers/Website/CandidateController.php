<?php

namespace App\Http\Controllers\Website;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\User;
use App\Models\Company;
use App\Models\JobRole;
use App\Models\Candidate;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profession;
use App\Models\SocialLink;
use App\Models\ContactInfo;
use App\Models\Nationality;
use Illuminate\Http\Request;
use App\Models\CandidateResume;
use App\Http\Traits\Candidateable;
use Modules\Location\Entities\City;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Location\Entities\State;
use Modules\Location\Entities\Country;
use Illuminate\Support\Facades\Validator;

class CandidateController extends Controller
{
    use Candidateable;

    public function activate(){

        return view('auth.activate');

    }

    public function activation(Request $request){


        //the required details are amount and mobile of the user

                      $consumerKey = "NeqHYS98NGGibVdg1kSJ0QkL6TTQeA4r"; //Fill with your app Consumer Key
                      $consumerSecret = "CYnFOaAGlb0Ao2XY"; // Fill with your app Secret
                    
                      # define the variales
                      # provide the following details, this part is found on your test credentials on the developer account
                      $BusinessShortCode ="4070303";
                      $Passkey = "7954b40292233647350b701e5686c152f8c21a891dcfe644f629d1ccf250168f";
                      
                      
                      
                      $PartyA = $request->mobile; // This is your phone number, 
                      $AccountReference = $request->accref;
                      $TransactionDesc = $request->accref;
                      $Amount = $request->amount;
                     
                      # Get the timestamp, format YYYYmmddhms -> 20181004151020
                      $Timestamp = date('YmdHis');    
                      
                      # Get the base64 encoded string -> $password. The passkey is the M-PESA Public Key
                      $Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);
                    
                      # header for access token
                      $headers = ['Content-Type:application/json; charset=utf8'];
                    
                        # M-PESA endpoint urls
                      $access_token_url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
                      $initiate_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
                    
                      # callback url
                      $CallBackURL = "https://lifegeegs.com/pay/confirmation_url.php";  
                    
                      $curl = curl_init($access_token_url);
                      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                      curl_setopt($curl, CURLOPT_HEADER, FALSE);
                      curl_setopt($curl, CURLOPT_USERPWD, $consumerKey.':'.$consumerSecret);
                      $result = curl_exec($curl);
                      $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                      $result = json_decode($result);
                      $access_token = $result->access_token;  
                      curl_close($curl);
                    
                      # header for stk push
                      $stkheader = ['Content-Type:application/json','Authorization:Bearer '.$access_token];
                    
                      # initiating the transaction
                      $curl = curl_init();
                      curl_setopt($curl, CURLOPT_URL, $initiate_url);
                      curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader); //setting custom header
                    
                      $curl_post_data = array(
                        //Fill in the request parameters with valid values
                        'BusinessShortCode' => $BusinessShortCode,
                        'Password' => $Password,
                        'Timestamp' => $Timestamp,
                        'TransactionType' => 'CustomerPayBillOnline',
                        'Amount' => $Amount,
                        'PartyA' => $PartyA,
                        'PartyB' => $BusinessShortCode,
                        'InvoiceNumber'=>'https://lifegeegs.com/status',
                        'PhoneNumber' => $PartyA,
                        'CallBackURL' => $CallBackURL,
                        'AccountReference' => $AccountReference,
                        'TransactionDesc' => $TransactionDesc
                      );
                    
                      $data_string = json_encode($curl_post_data);
                      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                      curl_setopt($curl, CURLOPT_POST, true);
                      curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                      $curl_response = curl_exec($curl);
                    //   print_r($curl_response);
                    
                    //   echo $curl_response;
                    $response=json_decode($curl_response);

                    if($response->CustomerMessage=="Success. Request accepted for processing"){
                            //tell user about the status of the transaction
                            return redirect()->back()->with('success', 'Success. Request accepted for processing');
                    }else{
                        return redirect()->back()->with('error', 'An error occured');
                    }

                 


    }

    public function dashboard()
    {
        $candidate = Candidate::where('user_id', auth()->id())->first();

        if (empty($candidate)) {

            $candidate = new Candidate();
            $candidate->user_id = auth()->id();
            $candidate->save();
        }

        //check the status of this candidate and redirect to activate the candidate

        if($candidate->status==0){
            return redirect('/candidate/activate');
        }

        $appliedJobs = $candidate->appliedJobs->count();
        $favoriteJobs = $candidate->bookmarkJobs->count();
        $jobs = $candidate->appliedJobs()->withCount(['bookmarkJobs as bookmarked' => function ($q) use ($candidate) {
            $q->where('candidate_id',  $candidate->id);
        }])
            ->latest()->limit(4)->get();
        $notifications = auth('user')->user()->notifications()->count();

        return view('website.pages.candidate.dashboard', compact('candidate', 'appliedJobs', 'jobs', 'favoriteJobs', 'notifications'));
    }

    public function allNotification()
    {

        $notifications = auth()->user()->notifications()->paginate(12);

        return view('website.pages.candidate.all-notification', compact('notifications'));
    }

    public function jobAlerts()
    {

        $notifications = auth()->user()->notifications()->where('type', 'App\Notifications\Website\Candidate\RelatedJobNotification')->paginate(12);

        return view('website.pages.candidate.job-alerts', compact('notifications'));
    }

    public function appliedjobs(Request $request)
    {

        $candidate = Candidate::where('user_id', auth()->id())->first();
        if (empty($candidate)) {

            $candidate = new Candidate();
            $candidate->user_id = auth()->id();
            $candidate->save();
        }

        $appliedJobs = $candidate->appliedJobs()->paginate(8);

        return view('website.pages.candidate.applied-jobs', compact('appliedJobs'));
    }

    public function bookmarks(Request $request)
    {
        $candidate = Candidate::where('user_id', auth()->id())->first();
        if (empty($candidate)) {

            $candidate = new Candidate();
            $candidate->user_id = auth()->id();
            $candidate->save();
        }

        $jobs = $candidate->bookmarkJobs()->withCount(['appliedJobs as applied' => function ($q) use ($candidate) {
            $q->where('candidate_id',  $candidate->id);
        }])->paginate(12);

        if (auth('user')->check() && auth('user')->user()->role == 'candidate') {
            $resumes = auth('user')->user()->candidate->resumes;
        }else{
            $resumes = [];
        }

        return view('website.pages.candidate.bookmark', compact('jobs','resumes'));
    }

    public function bookmarkCompany(Company $company)
    {

        $company->bookmarkCandidateCompany()->toggle(auth('user')->user()->candidate);
        return back();
    }

    public function setting()
    {
        $candidate = auth()->user()->candidate;

        if (empty($candidate)) {
            Candidate::create([
                'user_id' => auth()->id()
            ]);
        }

        // for contact
        $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
        $contact = [];
        if ($contactInfo) {
            $contact = $contactInfo;
        } else {
            $contact = '';
        }

        // for social link
        $socials = auth()->user()->socialInfo;

        // for candidate resume/cv
       $resumes = $candidate->resumes;

        $job_roles = JobRole::all();
        $experiences = Experience::all();
        $educations = Education::all();
        $nationalities = Nationality::all();
        $professions = Profession::all();

        return view('website.pages.candidate.setting', [
            'candidate' => $candidate,
            'contact' => $contact,
            'socials' => $socials,
            'job_roles' => $job_roles,
            'experiences' => $experiences,
            'educations' => $educations,
            'nationalities' => $nationalities,
            'professions' => $professions,
            'resumes' => $resumes,
        ]);
    }

    public function getState(Request $request)
    {

        $states = State::where('country_id', $request->country_id)->get();
        return response()->json($states);
    }

    public function getCity(Request $request)
    {

        $cities = City::where('state_id', $request->state_id)->get();
        return response()->json($cities);
    }

    public function settingUpdate(Request $request)
    {
        // return $request;
        $user = User::FindOrFail(auth()->id());
        $candidate = Candidate::where('user_id', $user->id)->first();
        $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
        $request->session()->put('type', $request->type);

        if ($request->type == 'basic') {
            $this->candidateBasicInfoUpdate($request, $user, $candidate);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'profile') {
            $this->candidateProfileInfoUpdate($request, $user, $candidate, $contactInfo);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'social') {
            $this->socialUpdate($request);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'contact') {
            $this->contactUpdate($request, $candidate);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'alert') {
            $this->alertUpdate($request, $user, $candidate);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'visibility') {
            $this->visibilityUpdate($request, $candidate);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'password') {
            $this->passwordUpdate($request, $user, $candidate);
            flashSuccess('Profile Updated');
            return back();
        }

        if ($request->type == 'account-delete') {
            $this->accountDelete($user);
        }

        return back();
    }

    public function candidateBasicInfoUpdate($request, $user, $candidate)
    {
        $request->validate([
            'name' => 'required',
            'education' =>  'required',
            'experience' =>  'required',
        ]);
        $user->update(['name' => $request->name]);

        // Experience
        $experience_request = $request->experience;
        $experience = Experience::where('id',$experience_request)->first();

        if (!$experience) {
            $experience = Experience::create(['name' => $experience_request]);
        }

        // Education
        $education_request = $request->education;
        $education = Education::where('id',$education_request)->first();

        if (!$education) {
            $education = Education::create(['name' => $education_request]);
        }

        $candidate->update([
            'title' => $request->title,
            'experience_id' => $experience->id,
            'education_id' => $education->id,
            'website' => $request->website,
        ]);

        // image
        if ($request->image) {
            $request->validate([
                'image' =>  'image|mimes:jpeg,png,jpg,|max:2048'
            ]);

            deleteImage($candidate->photo);
            $path = 'images/candidates';
            $image = uploadImage($request->image, $path);

            $candidate->update([
                "photo" => $image,
            ]);
        }
        // cv
        if ($request->cv) {
            $request->validate([
                "cv" => "mimetypes:application/pdf,jpeg,docs|max:5048",
            ]);
            $pdfPath = "/file/candidates/";
            $pdf = pdfUpload($request->cv, $pdfPath);

            $candidate->update([
                "cv" => $pdf,
            ]);
        }
        return true;
    }

    public function candidateProfileInfoUpdate($request, $User, $candidate, $contactInfo)
    {
        $request->validate([
            'birth_date' => 'date',
            'nationality' => 'required',
            'birth_date' =>  'required',
            'gender' => 'required',
            'marital_status' => 'required',
            'profession' => 'required',
        ]);

        $dateTime = Carbon::parse($request->birth_date);
        $date = $request['birth_date'] = $dateTime->format('Y-m-d H:i:s');

        // Profession
        $profession_request = $request->profession;
        $profession = Profession::where('id',$profession_request)->orWhere('name',$profession_request)->first();

        if (!$profession) {
            $profession = Profession::create(['name' => $profession_request]);
        }

        $candidate->update([
            'birth_date' => $date,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'bio' => $request->bio,
            'nationality_id' => $request->nationality,
            'profession_id' => $profession->id,
        ]);

        return true;
    }

    public function contactUpdate($request)
    {
        $contact = ContactInfo::where('user_id', auth()->id())->first();

        if (empty($contact)) {
            ContactInfo::create([
                'user_id' => auth()->id(),
                'phone' => $request->phone,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,
            ]);
        } else {
            $contact->update([
                'phone' => $request->phone,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,
            ]);
        }

        // Location
        updateMap(auth()->user()->candidate);

        return true;
    }

    public function socialUpdate($request)
    {
        $user = User::find(auth()->id());

        $user->socialInfo()->delete();
        $social_medias = $request->social_media;
        $urls = $request->url;

        if ($social_medias && $urls) {
            foreach ($social_medias as $key => $value) {
                if ($value && $urls[$key]) {
                    $user->socialInfo()->create([
                        'social_media' => $value,
                        'url' => $urls[$key],
                    ]);
                }
            }
        }

        return true;
    }

    public function visibilityUpdate($request, $candidate)
    {
        $candidate->update([
            'visibility' => $request->profile_visibility ? 1 : 0,
            'cv_visibility' => $request->cv_visibility ? 1 : 0
        ]);

        return true;
    }

    public function passwordUpdate($request, $user, $candidate)
    {
        $request->validate([
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required'
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);
        auth()->logout();

        return true;
    }

    public function accountDelete($user)
    {
        $user->delete();
        return true;
    }

    public function alertUpdate($request, $user, $candidate)
    {
        $user->update([
            'recent_activities_alert' => $request->recent_activity ? 1 : 0,
            'job_expired_alert' => $request->job_expired ? 1 : 0,
            'new_job_alert' => $request->new_job ? 1 : 0,
            'shortlisted_alert' => $request->shortlisted ? 1 : 0
        ]);

         // Jobrole
         $candidate->update([
            'role_id' => $request->role_id,
            'received_job_alert' => $request->received_job_alert ? 1 : 0,
        ]);

        return true;
    }

    public function resumeStore(Request $request){
        $request->validate([
            'resume_name' => 'required',
            'resume_file' => 'required|mimes:pdf|max:5120',
        ]);

        $candidate = auth()->user()->candidate;
        $data['name'] = $request->resume_name;
        $data['candidate_id'] = $candidate->id;

        // cv
        if ($request->resume_file) {
            $pdfPath = "file/candidates/";
            $file = uploadFileToPublic($request->resume_file, $pdfPath);
            $data['file'] = $file;
        }

        CandidateResume::create($data);

        return back()->with('success', 'Resume added successfully');
    }

    public function resumeUpdate(Request $request){
        $request->validate([
            'resume_name' => 'required',
        ]);

        $resume = CandidateResume::where('id', $request->resume_id)->first();
        $candidate = auth()->user()->candidate;
        $data['name'] = $request->resume_name;
        $data['candidate_id'] = $candidate->id;

        // cv
        if ($request->resume_file) {
            $request->validate([
                'resume_file' => 'required|mimes:pdf|max:5120',
            ]);
            deleteFile($resume->file);
            $pdfPath = "file/candidates/";
            $file = uploadFileToPublic($request->resume_file, $pdfPath);
            $data['file'] = $file;
        }

        $resume->update($data);

        return back()->with('success', 'Resume updated successfully');
    }

    public function resumeDelete(CandidateResume $resume){
        deleteFile($resume->file);
        $resume->delete();

        return back()->with('success', 'Resume deleted successfully');
    }
}
