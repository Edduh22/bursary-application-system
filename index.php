<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Bursary Application System - Apply for educational financial assistance online."
    >

    <title>
        Bursary Application System
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         CUSTOM STYLING
    ====================================================== -->

    <style>

        /* =================================================
           GENERAL
        ================================================= */

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #212529;
        }


        /* =================================================
           NAVBAR
        ================================================= */

        .navbar {
            min-height: 65px;
        }

        .navbar-brand {
            color: #09234d !important;
            font-size: 18px;
        }

        .navbar .nav-link {
            color: #495057;
            font-weight: 500;
        }

        .navbar .nav-link:hover {
            color: #0d6efd;
        }


        /* =================================================
           HERO
        ================================================= */

        .hero-section {
            background: white;
            padding: 65px 20px 70px;
            border-bottom: 1px solid #e9ecef;
        }

        .hero-section h1 {
            color: #09234d;
            font-size: clamp(32px, 5vw, 52px);
            line-height: 1.15;
            margin-bottom: 0;
        }

        .hero-section .lead {
            font-size: 18px;
            margin-bottom: 0;
        }

        .hero-buttons {
            margin-top: 25px;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .btn {
            border-radius: 7px;
            font-weight: 600;
        }

        .btn-lg {
            padding: 10px 22px;
        }


        /* =================================================
           GENERAL SECTIONS
        ================================================= */

        .compact-section {
            padding: 50px 20px;
        }

        .section-heading {
            color: #09234d;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .section-description {
            color: #6c757d;
            margin-bottom: 30px;
        }


        /* =================================================
           HOW IT WORKS
        ================================================= */

        .how-section {
            background: #f5f7fb;
        }

        .step-card {
            background: white;
            border: 1px solid #e5e9ef;
            border-radius: 10px;
            padding: 22px 15px;
            height: 100%;
            transition: 0.2s ease;
        }

        .step-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.07);
        }

        .step-number {
            width: 45px;
            height: 45px;
            line-height: 45px;
            margin: 0 auto 12px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 15px;
        }

        .step-card h5 {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .step-card p {
            font-size: 14px;
            margin-bottom: 0;
        }


        /* =================================================
           ABOUT
        ================================================= */

        .about-section {
            background: white;
        }

        .about-box {
            max-width: 800px;
            margin: auto;
        }

        .about-box p {
            line-height: 1.7;
            color: #6c757d;
            margin-bottom: 0;
        }


        /* =================================================
           REQUIREMENTS
        ================================================= */

        .requirements-section {
            background: #f5f7fb;
        }

        .requirements-list {
            max-width: 650px;
            margin: auto;
        }

        .requirements-list .list-group-item {
            padding: 12px 16px;
            border-color: #e3e7ec;
            background: white;
        }

        .requirement-icon {
            color: #198754;
            font-weight: bold;
            margin-right: 8px;
        }


        /* =================================================
           FAQ
        ================================================= */

        .faq-section {
            background: white;
        }

        .faq-container {
            max-width: 850px;
            margin: auto;
        }

        .accordion-item {
            border: 1px solid #e1e5ea;
            margin-bottom: 8px;
            border-radius: 7px !important;
            overflow: hidden;
        }

        .accordion-button {
            font-weight: 600;
            padding: 15px 18px;
        }

        .accordion-button:not(.collapsed) {
            color: #09234d;
            background: #f1f5fb;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-body {
            color: #6c757d;
            line-height: 1.6;
            font-size: 14px;
        }


        /* =================================================
           CALL TO ACTION
        ================================================= */

        .cta-section {
            background: #09234d;
            color: white;
            padding: 45px 20px;
        }

        .cta-section h2 {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .cta-section p {
            color: #dbe6f5;
            margin-bottom: 22px;
        }


        /* =================================================
           FOOTER
        ================================================= */

        footer {
            background: #171b21;
            color: white;
            padding: 25px 20px;
        }

        footer p {
            margin-bottom: 5px;
            font-weight: 600;
        }

        footer small {
            color: #adb5bd;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .navbar-brand {
                font-size: 15px;
            }

            .hero-section {
                padding: 45px 15px 50px;
            }

            .hero-section h1 {
                font-size: 34px;
            }

            .hero-section .lead {
                font-size: 16px;
            }

            .hero-buttons .btn {
                width: 100%;
                margin: 5px 0 !important;
            }

            .compact-section {
                padding: 40px 15px;
            }

            .cta-section {
                padding: 40px 15px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================== -->

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">

    <div class="container">

        <!-- LOGO -->

        <a
            class="navbar-brand fw-bold"
            href="index.php"
        >

            🎓 BOMACHOGE BORABU BURSARY APPLICATION SYSTEM 

        </a>


        <!-- MOBILE MENU BUTTON -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="navbarNav"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >
                        Home
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#about"
                    >
                        About
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#requirements"
                    >
                        Requirements
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#faq"
                    >
                        FAQ
                    </a>

                </li>


                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">

                    <a
                        href="login.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Login
                    </a>

                </li>


                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">

                    <a
                        href="register.php"
                        class="btn btn-primary btn-sm"
                    >
                        Register
                    </a>

                </li>


            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================== -->

<section class="hero-section">

    <div class="container text-center">

        <h1 class="fw-bold">

            Apply For Your Bursary Online

        </h1>


        <p class="lead text-muted mt-3">

            Simple • Secure • Transparent • Accessible

        </p>


        <div class="hero-buttons">

            <a
                href="register.php"
                class="btn btn-primary btn-lg me-2"
            >
                Apply Now
            </a>


            <a
                href="login.php"
                class="btn btn-outline-primary btn-lg"
            >
                Login
            </a>

        </div>

    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================== -->

<section class="compact-section how-section">

    <div class="container">


        <div class="text-center">

            <h2 class="section-heading">

                How It Works

            </h2>


            <p class="section-description">

                Applying for a bursary is simple and straightforward.

            </p>

        </div>


        <div class="row g-3">


            <!-- STEP 1 -->

            <div class="col-12 col-md-6 col-lg-3">

                <div class="step-card text-center">

                    <div class="step-number">

                        01

                    </div>


                    <h5>

                        Register

                    </h5>


                    <p class="text-muted">

                        Create your applicant account.

                    </p>

                </div>

            </div>


            <!-- STEP 2 -->

            <div class="col-12 col-md-6 col-lg-3">

                <div class="step-card text-center">

                    <div class="step-number">

                        02

                    </div>


                    <h5>

                        Apply

                    </h5>


                    <p class="text-muted">

                        Complete your bursary application.

                    </p>

                </div>

            </div>


            <!-- STEP 3 -->

            <div class="col-12 col-md-6 col-lg-3">

                <div class="step-card text-center">

                    <div class="step-number">

                        03

                    </div>


                    <h5>

                        Submit

                    </h5>


                    <p class="text-muted">

                        Review and submit your application.

                    </p>

                </div>

            </div>


            <!-- STEP 4 -->

            <div class="col-12 col-md-6 col-lg-3">

                <div class="step-card text-center">

                    <div class="step-number">

                        04

                    </div>


                    <h5>

                        Track Status

                    </h5>


                    <p class="text-muted">

                        Follow your application progress online.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     ABOUT
========================================================== -->

<section
    id="about"
    class="compact-section about-section"
>

    <div class="container">


        <div class="about-box text-center">


            <h2 class="section-heading">

                About the Bursary

            </h2>


            <p>

                The Bursary Application System provides applicants
                with a simple and convenient way to apply for
                educational financial assistance online.

            </p>


        </div>

    </div>

</section>



<!-- =========================================================
     REQUIREMENTS
========================================================== -->

<section
    id="requirements"
    class="compact-section requirements-section"
>

    <div class="container">


        <div class="text-center">

            <h2 class="section-heading">

                Application Requirements

            </h2>


            <p class="section-description">

                Prepare the following documents before applying.

            </p>

        </div>


        <div class="requirements-list">


            <ul class="list-group shadow-sm">


                <li class="list-group-item">

                    <span class="requirement-icon">✓</span>

                    National ID / Birth Certificate

                </li>


                <li class="list-group-item">

                    <span class="requirement-icon">✓</span>

                    Admission Letter

                </li>


                <li class="list-group-item">

                    <span class="requirement-icon">✓</span>

                    Fee Structure

                </li>


                <li class="list-group-item">

                    <span class="requirement-icon">✓</span>

                    Academic Results

                </li>


                <li class="list-group-item">

                    <span class="requirement-icon">✓</span>

                    Parent / Guardian Information

                </li>


            </ul>

        </div>

    </div>

</section>



<!-- =========================================================
     FAQ
========================================================== -->

<section
    id="faq"
    class="compact-section faq-section"
>

    <div class="container">


        <div class="text-center">


            <h2 class="section-heading">

                Frequently Asked Questions

            </h2>


            <p class="section-description">

                Find answers to common questions.

            </p>


        </div>


        <div
            class="faq-container"
            id="faqAccordion"
        >


            <!-- FAQ 1 -->

            <div class="accordion-item">

                <h2 class="accordion-header">

                    <button
                        class="accordion-button"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqOne"
                        aria-expanded="true"
                        aria-controls="faqOne"
                    >

                        Who can apply?

                    </button>

                </h2>


                <div
                    id="faqOne"
                    class="accordion-collapse collapse show"
                    data-bs-parent="#faqAccordion"
                >

                    <div class="accordion-body">

                        Eligible students who meet the bursary
                        requirements can submit an application.

                    </div>

                </div>

            </div>



            <!-- FAQ 2 -->

            <div class="accordion-item">

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqTwo"
                        aria-expanded="false"
                        aria-controls="faqTwo"
                    >

                        Can I track my application?

                    </button>

                </h2>


                <div
                    id="faqTwo"
                    class="accordion-collapse collapse"
                    data-bs-parent="#faqAccordion"
                >

                    <div class="accordion-body">

                        Yes. Applicants can log in to their account
                        and track the status of their application.

                    </div>

                </div>

            </div>



            <!-- FAQ 3 -->

            <div class="accordion-item">

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqThree"
                        aria-expanded="false"
                        aria-controls="faqThree"
                    >

                        What happens after I submit my application?

                    </button>

                </h2>


                <div
                    id="faqThree"
                    class="accordion-collapse collapse"
                    data-bs-parent="#faqAccordion"
                >

                    <div class="accordion-body">

                        Your application will be reviewed by the
                        bursary administrators. You can continue
                        tracking the progress from your dashboard.

                    </div>

                </div>

            </div>



            <!-- FAQ 4 -->

            <div class="accordion-item">

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqFour"
                        aria-expanded="false"
                        aria-controls="faqFour"
                    >

                        How will I know if I have been awarded?

                    </button>

                </h2>


                <div
                    id="faqFour"
                    class="accordion-collapse collapse"
                    data-bs-parent="#faqAccordion"
                >

                    <div class="accordion-body">

                        Once your application has been processed,
                        your account will display the latest
                        application and award status.

                    </div>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     CALL TO ACTION
========================================================== -->

<section class="cta-section">

    <div class="container text-center">


        <h2>

            Ready to Apply?

        </h2>


        <p>

            Create your account and begin your bursary application today.

        </p>


        <a
            href="register.php"
            class="btn btn-light btn-lg"
        >

            Apply Now

        </a>


    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================== -->

<footer>

    <div class="container text-center">

        <p>

            🎓 Bursary Application System

        </p>


        <small>

            © 2026 All Rights Reserved.

        </small>

    </div>

</footer>



<!-- =========================================================
     BOOTSTRAP JAVASCRIPT
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>