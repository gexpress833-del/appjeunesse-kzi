<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarouselController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SocialVisitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques (vitrine)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Authentification (invités uniquement)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Comptes en attente de validation par l'administrateur
|--------------------------------------------------------------------------
*/
Route::get('/en-attente', [AuthController::class, 'pending'])->middleware('auth')->name('pending');

/*
|--------------------------------------------------------------------------
| Application (connecté + compte actif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {

    // Tableau de bord personnel / global
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/bilan', [DashboardController::class, 'bilan'])->name('dashboard.bilan');

    // Profil personnel
    Route::get('/profil', [AuthController::class, 'profile'])->name('profile.edit');
    Route::put('/profil', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Annuaire : consultation pour tous les comptes actifs
    Route::get('/membres', [MemberController::class, 'index'])->name('members.index');

    // Événements : consultation pour tous
    Route::get('/evenements', [EventController::class, 'index'])->name('events.index');

    // Galerie & direct : consultation pour tous
    Route::get('/galerie', [MediaController::class, 'gallery'])->name('gallery.index');
    Route::post('/galerie/telecharger', [MediaController::class, 'downloadSelected'])->name('gallery.download');

    // Visites sociales : consultation personnelle, gestion Social/Secrétariat/Admin
    Route::get('/visites-sociales', [SocialVisitController::class, 'index'])->name('social-visits.index');
    Route::middleware('role:admin,secretariat,responsable')->group(function () {
        Route::get('/visites-sociales/creer', [SocialVisitController::class, 'create'])->name('social-visits.create');
        Route::post('/visites-sociales', [SocialVisitController::class, 'store'])->name('social-visits.store');
        Route::get('/visites-sociales/{socialVisit}/modifier', [SocialVisitController::class, 'edit'])->name('social-visits.edit');
        Route::put('/visites-sociales/{socialVisit}', [SocialVisitController::class, 'update'])->name('social-visits.update');
        Route::delete('/visites-sociales/{socialVisit}', [SocialVisitController::class, 'destroy'])->name('social-visits.destroy');
    });

    // Route publique détaillée après les routes statiques pour éviter les collisions
    Route::get('/membres/{member}', [MemberController::class, 'show'])->whereNumber('member')->name('members.show');
    Route::get('/evenements/{event}', [EventController::class, 'show'])->whereNumber('event')->name('events.show');

    /*
    |----------------------------------------------------------------------
    | Gestion des membres — responsable (son département), secrétariat, admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,secretariat,responsable')->group(function () {
        Route::get('/membres/creer', [MemberController::class, 'create'])->name('members.create');
        Route::post('/membres', [MemberController::class, 'store'])->name('members.store');
        Route::get('/membres/{member}/modifier', [MemberController::class, 'edit'])->name('members.edit');
        Route::put('/membres/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/membres/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Événements — création/gestion : secrétariat & admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,secretariat')->group(function () {
        Route::get('/evenements/creer', [EventController::class, 'create'])->name('events.create');
        Route::post('/evenements', [EventController::class, 'store'])->name('events.store');
        Route::get('/evenements/{event}/modifier', [EventController::class, 'edit'])->name('events.edit');
        Route::put('/evenements/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/evenements/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Présences — responsable : son département ; secrétariat & admin : tout
    |----------------------------------------------------------------------
    */
    Route::get('/presences', [AttendanceController::class, 'pick'])->name('attendances.pick');
    Route::get('/presences/{event}', [AttendanceController::class, 'sheet'])->name('attendances.sheet');
    Route::post('/presences/{event}', [AttendanceController::class, 'store'])->name('attendances.store');

    // Rapports globaux : secrétariat & admin
    Route::middleware('role:admin,secretariat')->group(function () {
        Route::get('/rapports', [AttendanceController::class, 'report'])->name('attendances.report');
        Route::get('/rapports/pdf', [AttendanceController::class, 'exportPdf'])->name('attendances.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | Galerie & Live — téléversement réservé au responsable DCC/Médias ou admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,responsable')->group(function () {
        Route::get('/galerie/publier', [MediaController::class, 'uploadForm'])->name('gallery.upload');
        Route::post('/galerie', [MediaController::class, 'storePhotos'])->name('gallery.store');
        Route::delete('/galerie/{photo}', [MediaController::class, 'destroyPhoto'])->name('gallery.destroy');
        Route::get('/direct', [MediaController::class, 'liveForm'])->name('live.edit');
        Route::post('/direct', [MediaController::class, 'liveSave'])->name('live.save');
    });

    /*
    |----------------------------------------------------------------------
    | Carrousel (versets, témoignages, bannières) — secrétariat & admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,secretariat')->group(function () {
        Route::get('/carrousel', [CarouselController::class, 'index'])->name('carousel.index');
        Route::get('/carrousel/creer', [CarouselController::class, 'create'])->name('carousel.create');
        Route::post('/carrousel', [CarouselController::class, 'store'])->name('carousel.store');
        Route::get('/carrousel/{homeContent}/modifier', [CarouselController::class, 'edit'])->name('carousel.edit');
        Route::put('/carrousel/{homeContent}', [CarouselController::class, 'update'])->name('carousel.update');
        Route::patch('/carrousel/{homeContent}/basculer', [CarouselController::class, 'toggle'])->name('carousel.toggle');
        Route::delete('/carrousel/{homeContent}', [CarouselController::class, 'destroy'])->name('carousel.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Comptes utilisateurs — création : secrétariat & admin ; validation :
    | rôles & statuts : admin uniquement
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,secretariat')->group(function () {
        Route::get('/utilisateurs/creer', [UserController::class, 'create'])->name('users.create');
        Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
        Route::patch('/utilisateurs/{user}/valider', [UserController::class, 'validateAccount'])->name('users.validate');
        Route::patch('/utilisateurs/{user}/role', [UserController::class, 'assignRole'])->name('users.role');
        Route::patch('/utilisateurs/{user}/statut', [UserController::class, 'setStatus'])->name('users.status');
    });
});
