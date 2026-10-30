<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Vertscend Automation Private Limited
 - Simple. Transparent. Real-Time.</title>
    <meta name="description"
        content="Track your bills with confidence using Planedge's real-time bill tracking system for clients, contractors, and teams">
    <meta name="keywords" content="bill tracker, project management, real-time tracking, budget vs actual, planedge">

    <!-- Favicons -->
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.0.9/css/glightbox.min.css"
        integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

    <!-- =======================================================
  * Template Name: BizLand
  * Template URL: https://bootstrapmade.com/bizland-bootstrap-business-template/
  * Updated: Aug 07 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
    <style>
        .featured-services .service-item {
            width: 100% !important;
        }

        /**
        * Template Name: BizLand
        * Template URL: https://bootstrapmade.com/bizland-bootstrap-business-template/
        * Updated: Aug 07 2024 with Bootstrap v5.3.3
        * Author: BootstrapMade.com
        * License: https://bootstrapmade.com/license/
        */

        /*--------------------------------------------------------------
        # Font & Color Variables
        # Help: https://bootstrapmade.com/color-system/
        --------------------------------------------------------------*/
        /* Fonts */
        :root {
            --default-font: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            --heading-font: "Montserrat", sans-serif;
            --nav-font: "Open Sans", sans-serif;
        }

        /* Global Colors - The following color variables are used throughout the website. Updating them here will change the color scheme of the entire website */
        :root {
            --background-color: #ffffff;
            /* Background color for the entire website, including individual sections */
            --default-color: #444444;
            /* Default color used for the majority of the text content across the entire website */
            --heading-color: #222222;
            /* Color for headings, subheadings and title throughout the website */
            --accent-color: #106eea;
            /* Accent color that represents your brand on the website. It's used for buttons, links, and other elements that need to stand out */
            --surface-color: #ffffff;
            /* The surface color is used as a background of boxed elements within sections, such as cards, icon boxes, or other elements that require a visual separation from the global background. */
            --contrast-color: #ffffff;
            /* Contrast color for text, ensuring readability against backgrounds of accent, heading, or default colors. */
        }

        /* Nav Menu Colors - The following color variables are used specifically for the navigation menu. They are separate from the global colors to allow for more customization options */
        :root {
            --nav-color: #222222;
            /* The default color of the main navmenu links */
            --nav-hover-color: #106eea;
            /* Applied to main navmenu links when they are hovered over or active */
            --nav-mobile-background-color: #ffffff;
            /* Used as the background color for mobile navigation menu */
            --nav-dropdown-background-color: #ffffff;
            /* Used as the background color for dropdown items that appear when hovering over primary navigation items */
            --nav-dropdown-color: #222222;
            /* Used for navigation links of the dropdown items in the navigation menu. */
            --nav-dropdown-hover-color: #106eea;
            /* Similar to --nav-hover-color, this color is applied to dropdown navigation links when they are hovered over. */
        }

        /* Color Presets - These classes override global colors when applied to any section or element, providing reuse of the sam color scheme. */

        .light-background {
            --background-color: #f5f9ff;
            --surface-color: #ffffff;
        }

        .dark-background {
            --background-color: #060606;
            --default-color: #ffffff;
            --heading-color: #ffffff;
            --surface-color: #252525;
            --contrast-color: #ffffff;
        }

        /* Smooth scroll */
        :root {
            scroll-behavior: smooth;
        }

        /*--------------------------------------------------------------
        # General Styling & Shared Classes
        --------------------------------------------------------------*/
        body {
            color: var(--default-color);
            background-color: var(--background-color);
            font-family: var(--default-font);
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
            transition: 0.3s;
        }

        a:hover {
            color: color-mix(in srgb, var(--accent-color), transparent 25%);
            text-decoration: none;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: var(--heading-color);
            font-family: var(--heading-font);
        }

        /* PHP Email Form Messages
        ------------------------------*/
        .php-email-form .error-message {
            display: none;
            background: #df1529;
            color: #ffffff;
            text-align: left;
            padding: 15px;
            margin-bottom: 24px;
            font-weight: 600;
        }

        .php-email-form .sent-message {
            display: none;
            color: #ffffff;
            background: #059652;
            text-align: center;
            padding: 15px;
            margin-bottom: 24px;
            font-weight: 600;
        }

        .php-email-form .loading {
            display: none;
            background: var(--surface-color);
            text-align: center;
            padding: 15px;
            margin-bottom: 24px;
        }

        .php-email-form .loading:before {
            content: "";
            display: inline-block;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            margin: 0 10px -6px 0;
            border: 3px solid var(--accent-color);
            border-top-color: var(--surface-color);
            animation: php-email-form-loading 1s linear infinite;
        }

        @keyframes php-email-form-loading {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /*--------------------------------------------------------------
    # Global Header
    --------------------------------------------------------------*/
        .header {
            color: var(--default-color);
            transition: all 0.5s;
            z-index: 997;
            background-color: var(--background-color);
        }

        .header .topbar {
            background-color: var(--accent-color);
            height: 40px;
            padding: 0;
            font-size: 14px;
            transition: all 0.5s;
        }

        .header .topbar .contact-info i {
            font-style: normal;
            color: var(--contrast-color);
        }

        .header .topbar .contact-info i a,
        .header .topbar .contact-info i span {
            padding-left: 5px;
            color: var(--contrast-color);
        }

        @media (max-width: 575px) {

            .header .topbar .contact-info i a,
            .header .topbar .contact-info i span {
                font-size: 13px;
            }
        }

        .header .topbar .contact-info i a {
            line-height: 0;
            transition: 0.3s;
        }

        .header .topbar .contact-info i a:hover {
            color: var(--contrast-color);
            text-decoration: underline;
        }

        .header .topbar .social-links a {
            color: color-mix(in srgb, var(--contrast-color), transparent 40%);
            line-height: 0;
            transition: 0.3s;
            margin-left: 20px;
        }

        .header .topbar .social-links a:hover {
            color: var(--contrast-color);
        }

        .header .branding {
            background-color: var(--background-color);
            min-height: 60px;
            padding: 10px 0;
        }

        .header .logo {
            line-height: 1;
        }

        .header .logo img {
            max-height: 56px;
            margin-right: 8px;
        }

        .header .logo h1 {
            font-size: 30px;
            margin: 0;
            font-weight: 700;
            color: var(--heading-color);
        }

        .scrolled .header {
            box-shadow: 0px 0 18px rgba(0, 0, 0, 0.1);
        }

        .scrolled .header .topbar {
            height: 0;
            visibility: hidden;
            overflow: hidden;
        }

        /*--------------------------------------------------------------
    # Navigation Menu
    --------------------------------------------------------------*/
        /* Navmenu - Desktop */
        @media (min-width: 1200px) {
            .navmenu {
                padding: 0;
            }

            .navmenu ul {
                margin: 0;
                padding: 0;
                display: flex;
                list-style: none;
                align-items: center;
            }

            .navmenu li {
                position: relative;
            }

            .navmenu a,
            .navmenu a:focus {
                color: var(--nav-color);
                padding: 18px 15px;
                font-size: 15px;
                font-family: var(--nav-font);
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: space-between;
                white-space: nowrap;
                transition: 0.3s;
            }

            .navmenu a i,
            .navmenu a:focus i {
                font-size: 12px;
                line-height: 0;
                margin-left: 5px;
                transition: 0.3s;
            }

            .navmenu li:last-child a {
                padding-right: 0;
            }

            .navmenu li:hover>a,
            .navmenu .active,
            .navmenu .active:focus {
                color: var(--nav-hover-color);
            }

            .navmenu .dropdown ul {
                margin: 0;
                padding: 10px 0;
                background: var(--nav-dropdown-background-color);
                display: block;
                position: absolute;
                visibility: hidden;
                left: 14px;
                top: 130%;
                opacity: 0;
                transition: 0.3s;
                border-radius: 4px;
                z-index: 99;
                box-shadow: 0px 0px 30px rgba(0, 0, 0, 0.1);
            }

            .navmenu .dropdown ul li {
                min-width: 200px;
            }

            .navmenu .dropdown ul a {
                padding: 10px 20px;
                font-size: 15px;
                text-transform: none;
                color: var(--nav-dropdown-color);
            }

            .navmenu .dropdown ul a i {
                font-size: 12px;
            }

            .navmenu .dropdown ul a:hover,
            .navmenu .dropdown ul .active:hover,
            .navmenu .dropdown ul li:hover>a {
                color: var(--nav-dropdown-hover-color);
            }

            .navmenu .dropdown:hover>ul {
                opacity: 1;
                top: 100%;
                visibility: visible;
            }

            .navmenu .dropdown .dropdown ul {
                top: 0;
                left: -90%;
                visibility: hidden;
            }

            .navmenu .dropdown .dropdown:hover>ul {
                opacity: 1;
                top: 0;
                left: -100%;
                visibility: visible;
            }
        }

        /* Navmenu - Mobile */
        @media (max-width: 1199px) {
            .mobile-nav-toggle {
                color: var(--nav-color);
                font-size: 28px;
                line-height: 0;
                margin-right: 10px;
                cursor: pointer;
                transition: color 0.3s;
            }

            .navmenu {
                padding: 0;
                z-index: 9997;
            }

            .navmenu ul {
                display: none;
                list-style: none;
                position: absolute;
                inset: 60px 20px 20px 20px;
                padding: 10px 0;
                margin: 0;
                border-radius: 6px;
                background-color: var(--nav-mobile-background-color);
                overflow-y: auto;
                transition: 0.3s;
                z-index: 9998;
                box-shadow: 0px 0px 30px rgba(0, 0, 0, 0.1);
            }

            .navmenu a,
            .navmenu a:focus {
                color: var(--nav-dropdown-color);
                padding: 10px 20px;
                font-family: var(--nav-font);
                font-size: 17px;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: space-between;
                white-space: nowrap;
                transition: 0.3s;
            }

            .navmenu a i,
            .navmenu a:focus i {
                font-size: 12px;
                line-height: 0;
                margin-left: 5px;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: 0.3s;
                background-color: color-mix(in srgb, var(--accent-color), transparent 90%);
            }

            .navmenu a i:hover,
            .navmenu a:focus i:hover {
                background-color: var(--accent-color);
                color: var(--contrast-color);
            }

            .navmenu a:hover,
            .navmenu .active,
            .navmenu .active:focus {
                color: var(--nav-dropdown-hover-color);
            }

            .navmenu .active i,
            .navmenu .active:focus i {
                background-color: var(--accent-color);
                color: var(--contrast-color);
                transform: rotate(180deg);
            }

            .navmenu .dropdown ul {
                position: static;
                display: none;
                z-index: 99;
                padding: 10px 0;
                margin: 10px 20px;
                background-color: var(--nav-dropdown-background-color);
                border: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
                box-shadow: none;
                transition: all 0.5s ease-in-out;
            }

            .navmenu .dropdown ul ul {
                background-color: rgba(33, 37, 41, 0.1);
            }

            .navmenu .dropdown>.dropdown-active {
                display: block;
                background-color: rgba(33, 37, 41, 0.03);
            }

            .mobile-nav-active {
                overflow: hidden;
            }

            .mobile-nav-active .mobile-nav-toggle {
                color: #fff;
                position: absolute;
                font-size: 32px;
                top: 15px;
                right: 15px;
                margin-right: 0;
                z-index: 9999;
            }

            .mobile-nav-active .navmenu {
                position: fixed;
                overflow: hidden;
                inset: 0;
                background: rgba(33, 37, 41, 0.8);
                transition: 0.3s;
            }

            .mobile-nav-active .navmenu>ul {
                display: block;
            }
        }

        /*--------------------------------------------------------------
    # Global Footer
    --------------------------------------------------------------*/
        .footer {
            color: var(--default-color);
            background-color: var(--background-color);
            font-size: 14px;
            padding-bottom: 50px;
            position: relative;
        }

        .footer .footer-newsletter {
            background-color: color-mix(in srgb, var(--accent-color), transparent 96%);
            padding: 50px 0;
        }

        .footer .footer-newsletter h4 {
            font-size: 24px;
        }

        .footer .footer-newsletter .newsletter-form {
            margin-top: 30px;
            margin-bottom: 15px;
            padding: 6px 8px;
            position: relative;
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--accent-color), transparent 80%);
            box-shadow: 0px 2px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            transition: 0.3s;
            border-radius: 4px;
        }

        .footer .footer-newsletter .newsletter-form:focus-within {
            border-color: var(--accent-color);
        }

        .footer .footer-newsletter .newsletter-form input[type=email] {
            border: 0;
            padding: 4px;
            width: 100%;
            background-color: var(--surface-color);
            color: var(--default-color);
        }

        .footer .footer-newsletter .newsletter-form input[type=email]:focus-visible {
            outline: none;
        }

        .footer .footer-newsletter .newsletter-form input[type=submit] {
            border: 0;
            font-size: 16px;
            padding: 0 20px;
            margin: -7px -8px -7px 0;
            background: var(--accent-color);
            color: var(--contrast-color);
            transition: 0.3s;
            border-radius: 0 4px 4px 0;
        }

        .footer .footer-newsletter .newsletter-form input[type=submit]:hover {
            background: color-mix(in srgb, var(--accent-color), transparent 20%);
        }

        .footer .footer-top {
            padding-top: 50px;
        }

        .footer .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 50%);
            font-size: 16px;
            color: color-mix(in srgb, var(--default-color), transparent 20%);
            margin-right: 10px;
            transition: 0.3s;
        }

        .footer .social-links a:hover {
            color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .footer h4 {
            font-size: 16px;
            font-weight: bold;
            position: relative;
            padding-bottom: 12px;
        }

        .footer .footer-links {
            margin-bottom: 30px;
        }

        .footer .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer .footer-links ul i {
            margin-right: 3px;
            font-size: 12px;
            line-height: 0;
            color: var(--accent-color);
        }

        .footer .footer-links ul li {
            padding: 10px 0;
            display: flex;
            align-items: center;
        }

        .footer .footer-links ul li:first-child {
            padding-top: 0;
        }

        .footer .footer-links ul a {
            display: inline-block;
            color: color-mix(in srgb, var(--default-color), transparent 20%);
            line-height: 1;
        }

        .footer .footer-links ul a:hover {
            color: var(--accent-color);
        }

        .footer .footer-about a {
            color: var(--heading-color);
            font-size: 24px;
            font-weight: 600;
            font-family: var(--heading-font);
        }

        .footer .footer-contact p {
            margin-bottom: 5px;
        }

        .footer .copyright {
            padding-top: 25px;
            padding-bottom: 25px;
            border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
        }

        .footer .copyright p {
            margin-bottom: 0;
        }

        .footer .credits {
            margin-top: 6px;
            font-size: 13px;
        }

        /*--------------------------------------------------------------
    # Preloader
    --------------------------------------------------------------*/
        #preloader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            overflow: hidden;
            background-color: var(--background-color);
            transition: all 0.6s ease-out;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #preloader div {
            width: 13px;
            height: 13px;
            background-color: var(--accent-color);
            border-radius: 50%;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
            position: absolute;
            left: 50%;
        }

        #preloader div:nth-child(1) {
            left: calc(50% + 8px);
            animation: animate-preloader-1 0.6s infinite;
        }

        #preloader div:nth-child(2) {
            left: calc(50% + 8px);
            animation: animate-preloader-2 0.6s infinite;
        }

        #preloader div:nth-child(3) {
            left: calc(50% + 32px);
            animation: animate-preloader-2 0.6s infinite;
        }

        #preloader div:nth-child(4) {
            left: calc(50% + 56px);
            animation: animate-preloader-3 0.6s infinite;
        }

        @keyframes animate-preloader-1 {
            0% {
                transform: scale(0);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes animate-preloader-3 {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(0);
            }
        }

        @keyframes animate-preloader-2 {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(24px, 0);
            }
        }

        /*--------------------------------------------------------------
    # Scroll Top Button
    --------------------------------------------------------------*/
        .scroll-top {
            position: fixed;
            visibility: hidden;
            opacity: 0;
            right: 15px;
            bottom: 15px;
            z-index: 99999;
            background-color: var(--accent-color);
            width: 40px;
            height: 40px;
            border-radius: 4px;
            transition: all 0.4s;
        }

        .scroll-top i {
            font-size: 24px;
            color: var(--contrast-color);
            line-height: 0;
        }

        .scroll-top:hover {
            background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
            color: var(--contrast-color);
        }

        .scroll-top.active {
            visibility: visible;
            opacity: 1;
        }

        /*--------------------------------------------------------------
    # Disable aos animation delay on mobile devices
    --------------------------------------------------------------*/
        @media screen and (max-width: 768px) {
            [data-aos-delay] {
                transition-delay: 0 !important;
            }
        }

        /*--------------------------------------------------------------
    # Global Page Titles & Breadcrumbs
    --------------------------------------------------------------*/
        .page-title {
            --background-color: color-mix(in srgb, var(--default-color), transparent 96%);
            color: var(--default-color);
            background-color: var(--background-color);
            padding: 25px 0;
            position: relative;
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .page-title .breadcrumbs ol {
            display: flex;
            flex-wrap: wrap;
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 14px;
            font-weight: 400;
        }

        .page-title .breadcrumbs ol li+li {
            padding-left: 10px;
        }

        .page-title .breadcrumbs ol li+li::before {
            content: "/";
            display: inline-block;
            padding-right: 10px;
            color: color-mix(in srgb, var(--default-color), transparent 70%);
        }

        /*--------------------------------------------------------------
    # Global Sections
    --------------------------------------------------------------*/
        section,
        .section {
            color: var(--default-color);
            background-color: var(--background-color);
            padding: 60px 0;
            scroll-margin-top: 78px;
            overflow: clip;
        }

        @media (max-width: 1199px) {

            section,
            .section {
                scroll-margin-top: 60px;
            }
        }

        /*--------------------------------------------------------------
    # Global Section Titles
    --------------------------------------------------------------*/
        .section-title {
            text-align: center;
            padding-bottom: 60px;
            position: relative;
        }

        .section-title h2 {
            font-size: 13px;
            letter-spacing: 1px;
            font-weight: 700;
            padding: 8px 20px;
            margin: 0;
            background: color-mix(in srgb, var(--accent-color), transparent 90%);
            color: var(--accent-color);
            display: inline-block;
            text-transform: uppercase;
            border-radius: 50px;
            font-family: var(--default-font);
        }

        .section-title p {
            color: var(--heading-color);
            margin: 10px 0 0 0;
            font-size: 25px;
            font-weight: 700;
            font-family: var(--heading-font);
        }

        .section-title p .description-title {
            color: var(--accent-color);
        }

        /*--------------------------------------------------------------
    # Hero Section
    --------------------------------------------------------------*/
        .hero {
            width: 100%;
            min-height: 75vh;
            position: relative;
            padding: 60px 0;
            display: flex;
            align-items: center;
            background: url("https://bootstrapmade.com/content/demo/BizLand/assets/img/hero-bg.jpg") top left;
            background-size: cover;
        }

        .hero:before {
            content: "";
            background: color-mix(in srgb, var(--background-color), transparent 30%);
            position: absolute;
            bottom: 0;
            top: 0;
            left: 0;
            right: 0;
        }

        .hero .container {
            position: relative;
        }

        .hero h1 {
            margin: 0;
            font-size: 48px;
            font-weight: 700;
            line-height: 56px;
        }

        .hero h1 span {
            color: var(--accent-color);
        }

        .hero p {
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            margin: 5px 0 30px 0;
            font-size: 20px;
            font-weight: 400;
        }

        .hero .btn-get-started {
            color: var(--contrast-color);
            background: var(--accent-color);
            font-family: var(--heading-font);
            font-weight: 400;
            font-size: 16px;
            letter-spacing: 1px;
            display: inline-block;
            padding: 12px 30px;
            border-radius: 4px;
            transition: 0.5s;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }

        .hero .btn-get-started:hover {
            color: var(--contrast-color);
            background: color-mix(in srgb, var(--accent-color), transparent 15%);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }

        .hero .btn-watch-video {
            font-size: 16px;
            transition: 0.5s;
            margin-left: 25px;
            color: var(--default-color);
            font-weight: 600;
        }

        .hero .btn-watch-video i {
            color: var(--accent-color);
            font-size: 32px;
            transition: 0.3s;
            line-height: 0;
            margin-right: 8px;
        }

        .hero .btn-watch-video:hover {
            color: var(--accent-color);
        }

        .hero .btn-watch-video:hover i {
            color: color-mix(in srgb, var(--accent-color), transparent 15%);
        }

        .hero .animated {
            animation: up-down 2s ease-in-out infinite alternate-reverse both;
        }

        @media (max-width: 640px) {
            .hero h1 {
                font-size: 28px;
                line-height: 36px;
            }

            .hero p {
                font-size: 18px;
                line-height: 24px;
                margin-bottom: 30px;
            }

            .hero .btn-get-started,
            .hero .btn-watch-video {
                font-size: 13px;
            }
        }

        @keyframes up-down {
            0% {
                transform: translateY(10px);
            }

            100% {
                transform: translateY(-10px);
            }
        }

        /*--------------------------------------------------------------
    # Featured Services Section
    --------------------------------------------------------------*/
        .featured-services .service-item {
            background-color: var(--surface-color);
            box-shadow: 0px 0 25px 0 rgba(0, 0, 0, 0.1);
            padding: 50px 30px;
            transition: all 0.3s ease-in-out;
            height: 100%;
            position: relative;
            z-index: 1;
        }

        .featured-services .service-item:before {
            content: "";
            position: absolute;
            background: var(--accent-color);
            inset: 100% 0 0 0;
            transition: all 0.3s;
            z-index: -1;
        }

        .featured-services .service-item .icon {
            margin-bottom: 10px;
        }

        .featured-services .service-item .icon i {
            color: var(--accent-color);
            font-size: 36px;
            transition: ease-in-out 0.3s;
        }

        .featured-services .service-item h4 {
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .featured-services .service-item h4 a {
            color: var(--heading-color);
            transition: ease-in-out 0.3s;
        }

        .featured-services .service-item p {
            line-height: 24px;
            font-size: 14px;
            margin-bottom: 0;
            transition: ease-in-out 0.3s;
        }

        .featured-services .service-item:hover h4 a,
        .featured-services .service-item:hover .icon i,
        .featured-services .service-item:hover p {
            color: var(--contrast-color);
        }

        .featured-services .service-item:hover:before {
            background: var(--accent-color);
            inset: 0;
            border-radius: 0px;
        }

        /*--------------------------------------------------------------
    # About Section
    --------------------------------------------------------------*/
        .about .about-content h3 {
            font-weight: 700;
            font-size: 26px;
        }

        .about .about-content ul {
            list-style: none;
            padding: 0;
        }

        .about .about-content ul li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 35px;
        }

        .about .about-content ul li:first-child {
            margin-top: 35px;
        }

        .about .about-content ul i {
            background: var(--surface-color);
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 64px;
            height: 64px;
            font-size: 24px;
            margin-right: 15px;
            color: var(--accent-color);
            border-radius: 50px;
        }

        .about .about-content ul h4 {
            font-size: 18px;
            font-weight: 600;
        }

        .about .about-content ul p {
            font-size: 15px;
        }

        .about .about-content p:last-child {
            margin-bottom: 0;
        }

        /*--------------------------------------------------------------
    # Skills Section
    --------------------------------------------------------------*/
        .skills .progress {
            height: 60px;
            display: block;
            background: none;
            border-radius: 0;
        }

        .skills .progress .skill {
            color: var(--heading-color);
            padding: 0;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            display: block;
            font-weight: 600;
            font-family: var(--heading-font);
        }

        .skills .progress .skill .val {
            float: right;
            font-style: normal;
        }

        .skills .progress-bar-wrap {
            background: color-mix(in srgb, var(--default-color), transparent 90%);
            height: 10px;
        }

        .skills .progress-bar {
            width: 1px;
            height: 10px;
            transition: 0.9s;
            background-color: var(--accent-color);
        }

        /*--------------------------------------------------------------
    # Stats Section
    --------------------------------------------------------------*/
        .stats i {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            border: 6px solid var(--surface-color);
            width: 64px;
            height: 64px;
            font-size: 24px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .stats .stats-item {
            background-color: color-mix(in srgb, var(--accent-color), transparent 95%);
            margin-top: -32px;
            padding: 40px 30px 35px 30px;
            width: 100%;
            position: relative;
            text-align: center;
            border-radius: 4px;
            z-index: 0;
        }

        .stats .stats-item span {
            font-size: 36px;
            display: block;
            font-weight: 700;
        }

        .stats .stats-item p {
            padding: 0;
            margin: 0;
            font-family: var(--heading-font);
            font-size: 16px;
        }

        /*--------------------------------------------------------------
    # Clients Section
    --------------------------------------------------------------*/
        .clients {
            padding: 25px 0;
        }

        .clients .swiper {
            padding: 10px 0;
        }

        .clients .swiper-wrapper {
            height: auto;
        }

        .clients .swiper-slide img {
            transition: 0.3s;
        }

        .clients .swiper-slide img:hover {
            transform: scale(1.1);
        }

        /*--------------------------------------------------------------
    # Services Section
    --------------------------------------------------------------*/
        .services .service-item {
            background-color: var(--surface-color);
            text-align: center;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 85%);
            padding: 80px 20px;
            transition: border ease-in-out 0.3s;
            height: 100%;
        }

        .services .service-item .icon {
            margin: 0 auto;
            width: 64px;
            height: 64px;
            background: var(--accent-color);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .services .service-item .icon i {
            color: var(--contrast-color);
            font-size: 28px;
            transition: ease-in-out 0.3s;
        }

        .services .service-item h3 {
            font-weight: 700;
            margin: 10px 0 15px 0;
            font-size: 22px;
            transition: 0.3s;
        }

        .services .service-item p {
            line-height: 24px;
            font-size: 14px;
            margin-bottom: 0;
        }

        .services .service-item:hover {
            border-color: var(--accent-color);
        }

        .services .service-item:hover h3 {
            color: var(--accent-color);
        }

        /*--------------------------------------------------------------
    # Testimonials Section
    --------------------------------------------------------------*/
        .testimonials {
            padding: 80px 0;
            position: relative;
        }

        .testimonials:before {
            content: "";
            background: color-mix(in srgb, var(--background-color), transparent 30%);
            position: absolute;
            inset: 0;
            z-index: 2;
        }

        .testimonials .testimonials-bg {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .testimonials .container {
            position: relative;
            z-index: 3;
        }

        .testimonials .testimonials-carousel,
        .testimonials .testimonials-slider {
            overflow: hidden;
        }

        .testimonials .testimonial-item {
            text-align: center;
        }

        .testimonials .testimonial-item .testimonial-img {
            width: 100px;
            border-radius: 50%;
            border: 6px solid color-mix(in srgb, var(--default-color), transparent 85%);
            margin: 0 auto;
        }

        .testimonials .testimonial-item h3 {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }

        .testimonials .testimonial-item h4 {
            font-size: 14px;
            margin: 0 0 15px 0;
            color: color-mix(in srgb, var(--default-color), transparent 40%);
        }

        .testimonials .testimonial-item .stars {
            margin-bottom: 15px;
        }

        .testimonials .testimonial-item .stars i {
            color: #ffc107;
            margin: 0 1px;
        }

        .testimonials .testimonial-item .quote-icon-left,
        .testimonials .testimonial-item .quote-icon-right {
            color: color-mix(in srgb, var(--default-color), transparent 40%);
            font-size: 26px;
            line-height: 0;
        }

        .testimonials .testimonial-item .quote-icon-left {
            display: inline-block;
            left: -5px;
            position: relative;
        }

        .testimonials .testimonial-item .quote-icon-right {
            display: inline-block;
            right: -5px;
            position: relative;
            top: 10px;
            transform: scale(-1, -1);
        }

        .testimonials .testimonial-item p {
            font-style: italic;
            margin: 0 auto 15px auto;
        }

        .testimonials .swiper-wrapper {
            height: auto;
        }

        .testimonials .swiper-pagination {
            margin-top: 20px;
            position: relative;
        }

        .testimonials .swiper-pagination .swiper-pagination-bullet {
            width: 12px;
            height: 12px;
            background-color: color-mix(in srgb, var(--default-color), transparent 50%);
            opacity: 0.5;
        }

        .testimonials .swiper-pagination .swiper-pagination-bullet-active {
            background-color: var(--default-color);
            opacity: 1;
        }

        @media (min-width: 992px) {
            .testimonials .testimonial-item p {
                width: 80%;
            }
        }

        /*--------------------------------------------------------------
    # Portfolio Section
    --------------------------------------------------------------*/
        .portfolio .portfolio-filters {
            padding: 0;
            margin: 0 auto 20px auto;
            list-style: none;
            text-align: center;
        }

        .portfolio .portfolio-filters li {
            cursor: pointer;
            display: inline-block;
            padding: 8px 20px 10px 20px;
            margin: 0;
            font-size: 15px;
            font-weight: 500;
            line-height: 1;
            margin-bottom: 5px;
            border-radius: 4px;
            transition: all 0.3s ease-in-out;
            font-family: var(--heading-font);
        }

        .portfolio .portfolio-filters li:hover,
        .portfolio .portfolio-filters li.filter-active {
            color: var(--contrast-color);
            background-color: var(--accent-color);
        }

        .portfolio .portfolio-filters li:first-child {
            margin-left: 0;
        }

        .portfolio .portfolio-filters li:last-child {
            margin-right: 0;
        }

        @media (max-width: 575px) {
            .portfolio .portfolio-filters li {
                font-size: 14px;
                margin: 0 0 10px 0;
            }
        }

        .portfolio .portfolio-item {
            position: relative;
            overflow: hidden;
        }

        .portfolio .portfolio-item .portfolio-info {
            opacity: 0;
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: -100%;
            z-index: 3;
            transition: all ease-in-out 0.5s;
            background: color-mix(in srgb, var(--background-color), transparent 10%);
            padding: 15px;
        }

        .portfolio .portfolio-item .portfolio-info h4 {
            font-size: 18px;
            font-weight: 600;
            padding-right: 50px;
        }

        .portfolio .portfolio-item .portfolio-info p {
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            font-size: 14px;
            margin-bottom: 0;
            padding-right: 50px;
        }

        .portfolio .portfolio-item .portfolio-info .preview-link,
        .portfolio .portfolio-item .portfolio-info .details-link {
            position: absolute;
            right: 50px;
            font-size: 24px;
            top: calc(50% - 14px);
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            transition: 0.3s;
            line-height: 0;
        }

        .portfolio .portfolio-item .portfolio-info .preview-link:hover,
        .portfolio .portfolio-item .portfolio-info .details-link:hover {
            color: var(--accent-color);
        }

        .portfolio .portfolio-item .portfolio-info .details-link {
            right: 14px;
            font-size: 28px;
        }

        .portfolio .portfolio-item:hover .portfolio-info {
            opacity: 1;
            bottom: 0;
        }

        /*--------------------------------------------------------------
    # Team Section
    --------------------------------------------------------------*/
        .team .team-member {
            background-color: var(--surface-color);
            box-shadow: 0px 2px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-radius: 5px;
        }

        .team .team-member .member-img {
            position: relative;
            overflow: hidden;
        }

        .team .team-member .social {
            position: absolute;
            left: 0;
            bottom: 0;
            right: 0;
            height: 40px;
            opacity: 0;
            transition: ease-in-out 0.3s;
            background: color-mix(in srgb, var(--background-color), transparent 20%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .team .team-member .social a {
            transition: color 0.3s;
            color: var(--heading-color);
            margin: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .team .team-member .social a i {
            line-height: 0;
        }

        .team .team-member .social a:hover {
            color: var(--accent-color);
        }

        .team .team-member .social i {
            font-size: 18px;
            margin: 0 2px;
        }

        .team .team-member .member-info {
            padding: 25px 15px;
            text-align: center;
        }

        .team .team-member .member-info h4 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 18px;
            color: color-mix(in srgb, var(--default-color), transparent 20%);
        }

        .team .team-member .member-info span {
            display: block;
            font-size: 13px;
            font-weight: 400;
            color: color-mix(in srgb, var(--default-color), transparent 40%);
        }

        .team .team-member .member-info p {
            font-style: italic;
            font-size: 14px;
            line-height: 26px;
            color: color-mix(in srgb, var(--default-color), transparent 40%);
        }

        .team .team-member:hover .social {
            opacity: 1;
        }

        /*--------------------------------------------------------------
    # Pricing Section
    --------------------------------------------------------------*/
        .pricing .pricing-item {
            background-color: var(--surface-color);
            box-shadow: 0px 0px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            position: relative;
            overflow: hidden;
        }

        .pricing .pricing-item h3 {
            font-weight: 400;
            margin: -20px -20px 20px -20px;
            padding: 20px 15px;
            font-size: 16px;
            font-weight: 600;
            color: color-mix(in srgb, var(--default-color), transparent 20%);
            background: color-mix(in srgb, var(--default-color), transparent 95%);
        }

        .pricing .pricing-item h4 {
            font-size: 36px;
            font-weight: 600;
            font-family: var(--heading-font);
        }

        .pricing .pricing-item h4 sup {
            font-size: 20px;
            top: -15px;
            left: -3px;
        }

        .pricing .pricing-item h4 span {
            color: color-mix(in srgb, var(--default-color), transparent 40%);
            font-size: 16px;
            font-weight: 300;
        }

        .pricing .pricing-item ul {
            padding: 15px 0;
            list-style: none;
            text-align: center;
            line-height: 20px;
            font-size: 14px;
        }

        .pricing .pricing-item ul li {
            padding-bottom: 16px;
        }

        .pricing .pricing-item ul i {
            color: var(--accent-color);
            font-size: 18px;
            padding-right: 4px;
        }

        .pricing .pricing-item ul .na {
            color: color-mix(in srgb, var(--default-color), transparent 40%);
            text-decoration: line-through;
        }

        .pricing .btn-wrap {
            background: color-mix(in srgb, var(--default-color), transparent 95%);
            margin: 0 -20px -20px -20px;
            padding: 20px 15px;
            text-align: center;
        }

        .pricing .btn-buy {
            background: var(--accent-color);
            color: var(--contrast-color);
            display: inline-block;
            padding: 8px 35px 10px 35px;
            border-radius: 4px;
            transition: none;
            font-size: 14px;
            font-weight: 400;
            font-family: var(--heading-font);
            font-weight: 600;
            transition: 0.3s;
        }

        .pricing .btn-buy:hover {
            background: color-mix(in srgb, var(--accent-color), transparent 20%);
        }

        .pricing .featured h3 {
            background: var(--accent-color);
            color: var(--contrast-color);
        }

        .pricing .advanced {
            background: var(--accent-color);
            color: var(--contrast-color);
            width: 200px;
            position: absolute;
            top: 18px;
            right: -68px;
            transform: rotate(45deg);
            z-index: 1;
            font-size: 14px;
            padding: 1px 0 3px 0;
        }

        /*--------------------------------------------------------------
    # Faq Section
    --------------------------------------------------------------*/
        .faq .faq-container .faq-item {
            background-color: var(--surface-color);
            position: relative;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 85%);
            border-radius: 5px;
            overflow: hidden;
        }

        .faq .faq-container .faq-item:last-child {
            margin-bottom: 0;
        }

        .faq .faq-container .faq-item h3 {
            font-weight: 600;
            font-size: 18px;
            line-height: 24px;
            margin: 0 30px 0 0;
            transition: 0.3s;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .faq .faq-container .faq-item h3 .num {
            color: var(--accent-color);
            padding-right: 5px;
        }

        .faq .faq-container .faq-item h3:hover {
            color: var(--accent-color);
        }

        .faq .faq-container .faq-item .faq-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: 0.3s ease-in-out;
            visibility: hidden;
            opacity: 0;
        }

        .faq .faq-container .faq-item .faq-content p {
            margin-bottom: 0;
            overflow: hidden;
        }

        .faq .faq-container .faq-item .faq-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 16px;
            line-height: 0;
            transition: 0.3s;
            cursor: pointer;
        }

        .faq .faq-container .faq-item .faq-toggle:hover {
            color: var(--accent-color);
        }

        .faq .faq-container .faq-active {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--contrast-color);
        }

        .faq .faq-container .faq-active h3 {
            color: var(--contrast-color);
        }

        .faq .faq-container .faq-active .faq-content {
            grid-template-rows: 1fr;
            visibility: visible;
            opacity: 1;
            padding-top: 10px;
        }

        .faq .faq-container .faq-active .faq-toggle {
            transform: rotate(90deg);
            color: var(--accent-color);
        }

        /*--------------------------------------------------------------
    # Contact Section
    --------------------------------------------------------------*/
        .contact .info-wrap {
            background-color: var(--surface-color);
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        @media (max-width: 575px) {
            .contact .info-wrap {
                padding: 20px;
            }
        }

        .contact .info-item {
            margin-bottom: 40px;
        }

        .contact .info-item i {
            font-size: 20px;
            color: var(--accent-color);
            background: color-mix(in srgb, var(--accent-color), transparent 92%);
            width: 44px;
            height: 44px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50px;
            transition: all 0.3s ease-in-out;
            margin-right: 15px;
        }

        .contact .info-item h3 {
            padding: 0;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .contact .info-item p {
            padding: 0;
            margin-bottom: 0;
            font-size: 14px;
        }

        .contact .info-item:hover i {
            background: var(--accent-color);
            color: var(--contrast-color);
        }

        .contact .php-email-form {
            background-color: var(--surface-color);
            height: 100%;
            padding: 30px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 575px) {
            .contact .php-email-form {
                padding: 20px;
            }
        }

        .contact .php-email-form input[type=text],
        .contact .php-email-form input[type=email],
        .contact .php-email-form textarea {
            font-size: 14px;
            padding: 10px 15px;
            box-shadow: none;
            border-radius: 0;
            color: var(--default-color);
            background-color: var(--surface-color);
            border-color: color-mix(in srgb, var(--default-color), transparent 80%);
        }

        .contact .php-email-form input[type=text]:focus,
        .contact .php-email-form input[type=email]:focus,
        .contact .php-email-form textarea:focus {
            border-color: var(--accent-color);
        }

        .contact .php-email-form input[type=text]::placeholder,
        .contact .php-email-form input[type=email]::placeholder,
        .contact .php-email-form textarea::placeholder {
            color: color-mix(in srgb, var(--default-color), transparent 70%);
        }

        .contact .php-email-form button[type=submit] {
            color: var(--contrast-color);
            background: var(--accent-color);
            border: 0;
            padding: 10px 30px;
            transition: 0.4s;
            border-radius: 50px;
        }

        .contact .php-email-form button[type=submit]:hover {
            background: color-mix(in srgb, var(--accent-color), transparent 25%);
        }

        /*--------------------------------------------------------------
    # Service Details Section
    --------------------------------------------------------------*/
        .service-details .services-list {
            background-color: var(--surface-color);
            padding: 10px 30px;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
            margin-bottom: 20px;
        }

        .service-details .services-list a {
            display: block;
            line-height: 1;
            padding: 8px 0 8px 15px;
            border-left: 3px solid color-mix(in srgb, var(--default-color), transparent 70%);
            margin: 20px 0;
            color: color-mix(in srgb, var(--default-color), transparent 20%);
            transition: 0.3s;
        }

        .service-details .services-list a.active {
            color: var(--heading-color);
            font-weight: 700;
            border-color: var(--accent-color);
        }

        .service-details .services-list a:hover {
            border-color: var(--accent-color);
        }

        .service-details .services-img {
            margin-bottom: 20px;
        }

        .service-details h3 {
            font-size: 26px;
            font-weight: 700;
        }

        .service-details h4 {
            font-size: 20px;
            font-weight: 700;
        }

        .service-details p {
            font-size: 15px;
        }

        .service-details ul {
            list-style: none;
            padding: 0;
            font-size: 15px;
        }

        .service-details ul li {
            padding: 5px 0;
            display: flex;
            align-items: center;
        }

        .service-details ul i {
            font-size: 20px;
            margin-right: 8px;
            color: var(--accent-color);
        }

        /*--------------------------------------------------------------
    # Portfolio Details Section
    --------------------------------------------------------------*/
        .portfolio-details .portfolio-details-slider img {
            width: 100%;
        }

        .portfolio-details .portfolio-details-slider .swiper-pagination {
            margin-top: 20px;
            position: relative;
        }

        .portfolio-details .portfolio-details-slider .swiper-pagination .swiper-pagination-bullet {
            width: 12px;
            height: 12px;
            background-color: color-mix(in srgb, var(--default-color), transparent 85%);
            opacity: 1;
        }

        .portfolio-details .portfolio-details-slider .swiper-pagination .swiper-pagination-bullet-active {
            background-color: var(--accent-color);
        }

        .portfolio-details .portfolio-info {
            background-color: var(--surface-color);
            padding: 30px;
            box-shadow: 0px 0 30px rgba(0, 0, 0, 0.1);
        }

        .portfolio-details .portfolio-info h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 85%);
        }

        .portfolio-details .portfolio-info ul {
            list-style: none;
            padding: 0;
            font-size: 15px;
        }

        .portfolio-details .portfolio-info ul li+li {
            margin-top: 10px;
        }

        .portfolio-details .portfolio-description {
            padding-top: 30px;
        }

        .portfolio-details .portfolio-description h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .portfolio-details .portfolio-description p {
            padding: 0;
            color: color-mix(in srgb, var(--default-color), transparent 30%);
        }

        /*--------------------------------------------------------------
    # Starter Section Section
    --------------------------------------------------------------*/
        .starter-section {
            /* Add your styles here */
        }

        .process-step {
            text-align: center;
            padding: 30px 20px;
            background: var(--surface-color);
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            height: 100%;
            transition: transform 0.3s;
        }

        .process-step h4 {
            font-weight: 700;
            font-size: 20px;
            font-family: 'Montserrat';
        }

        .process-step:hover {
            transform: translateY(-10px);
        }

        .step-icon {
            width: 80px;
            height: 80px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .step-icon i {
            font-size: 36px;
            color: white;
        }

        .step-number {
            position: absolute;
            top: -15px;
            left: -15px;
            width: 40px;
            height: 40px;
            background: #ffd700;
            color: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>

<body class="index-page">

    <header id="header" class="header sticky-top">

        {{-- <div class="topbar d-flex align-items-center">
            <div class="container d-flex justify-content-center justify-content-md-between">
                <div class="contact-info d-flex align-items-center">
                    <i class="bi bi-envelope d-flex align-items-center"><a
                            href="mailto:info@planedge.in">info@planedge.in</a></i>
                    <i class="bi bi-phone d-flex align-items-center ms-4"><span>+1 5589 55488 55</span></i>
                </div>
                <div class="social-links d-none d-md-flex align-items-center">
                    <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div> --}}


        <div class="branding d-flex align-items-cente">

            <div class="container position-relative d-flex align-items-center justify-content-between">
                <a href="https://www.planedge.in/" class="logo d-flex align-items-center">
                    <img loading="lazy" src="{{ url('user-uploads/app-logo/44ab968499f8144cc4287599d2961b84.png') }}"
                        alt="PLANEDGE BILL TRACKER" height="100">
                </a>

                <nav id="navmenu" class="navmenu">
                    <ul>
                        <li><a href="#hero" class="active">Home</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#Expertise">Expertise</a></li>
                        <li><a href="#achievements">Achievements</a></li>
                        <li><a href="#contact">Contact</a></li>


                        <li><a href="{{ route('login') }}">Login</a></li>
                    </ul>
                    <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
                </nav>

            </div>

        </div>

    </header>

    <main class="main">

        <!-- Hero Section -->
        <section id="hero" class="hero section light-background">

            <div class="container">
                <div class="row gy-4">
                    <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center"
                        data-aos="zoom-out">
                        <h1>Track Your Bills with <span>Confidence</span></h1>
                        <p>Simple. Transparent. Real-Time.</p>
                        <p>A streamlined portal for clients, contractors, and internal teams to check bill status,
                            approvals, and progress of Budget vs Actual in real time.</p>
                        <div class="d-flex">
                            <a href="{{ route('login') }}" class="btn-get-started">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>

        </section><!-- /Hero Section -->
        
                      <section id="stats" class="stats section">
    <div class="container aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center">
                <i class="bi bi-check-circle"></i>
                <div class="stats-item">
                    <span class="counter" data-target="232">0</span>
                    <p>Projects Completed</p>
                </div>
            </div><!-- End Stats Item -->

            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center">
                <i class="bi bi-clipboard-data"></i>
                <div class="stats-item">
                    <span class="counter" data-target="521">0</span>
                    <p>VAS Projects Completed</p>
                </div>
            </div><!-- End Stats Item -->

            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center">
                <i class="bi bi-building"></i>
                <div class="stats-item">
                    <span class="counter" data-target="1463">0</span>
                    <p>Million sq. ft. Managed Area</p>
                </div>
            </div><!-- End Stats Item -->
        </div>
    </div>
</section>

        <!-- Featured Services Section -->
        <section id="how-it-works" class="featured-services section">
            <div class="container section-title aos-init aos-animate" data-aos="fade-up">
                <h2>Process</h2>
                <p><span>How It </span> <span class="description-title">Works</span></p>
            </div>
            <div class="container">


                <div class="row gy-4">
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="process-step position-relative">
                            <div class="step-number">1</div>
                            <div class="step-icon">
                                <i class="bi bi-search"></i>
                            </div>
                            <h4>Enter Bill Reference</h4>
                            <p>Use the unique bill number or project ID to retrieve your record instantly.</p>
                        </div>
                    </div>

                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="process-step position-relative">
                            <div class="step-number">2</div>
                            <div class="step-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h4>View Real-Time Status</h4>
                            <p>See approval stages, comments, pending actions, and expected timelines.</p>
                        </div>
                    </div>

                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="process-step position-relative">
                            <div class="step-number">3</div>
                            <div class="step-icon">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <h4>Stay Updated</h4>
                            <p>No follow-ups needed. Get notified as soon as your bill moves to the next stage.</p>
                        </div>
                    </div>
                </div>

            </div>

        </section><!-- /Featured Services Section -->

        <!-- Featured Services Section -->
        <section id="how-it-works" class="featured-services section">
            <div class="container section-title aos-init aos-animate" data-aos="fade-up">
                <p><span>Why this </span> <span class="description-title">Matters</span></p>
            </div>
            <div class="container">

                <div class="row  gy-4">

                    <div class="col-xl-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="100">
                        <div class="service-item position-relative">
                            <div class="icon"><i class="bi bi-check-circle-fill text-success fs-1 mb-3"></i></div>
                            <h4><a href="" class="stretched-link">Eliminates Manual Tracking</a></h4>
                            <p>No more spreadsheets or follow-up emails</p>
                        </div>
                    </div><!-- End Service Item -->

                    <div class="col-xl-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-item position-relative">
                            <div class="icon"><i class="bi bi-eye-fill text-primary fs-1 mb-3"></i></div>
                            <h4><a href="" class="stretched-link">Ensures Transparency</a></h4>
                            <p>
                                Clear visibility across all teams</p>
                        </div>
                    </div><!-- End Service Item -->

                    <div class="col-xl-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-item position-relative">
                            <div class="icon"> <i class="bi bi-lightning-fill text-warning fs-1 mb-3"></i>
                            </div>
                            <h4><a href="" class="stretched-link">Reduces Delays</a></h4>
                            <p>
                                Faster decisions and approvals</p>
                        </div>
                    </div><!-- End Service Item -->
                </div>

            </div>

        </section><!-- /Featured Services Section -->

        <!-- About Section -->
        <section id="about" class="about section light-background">

            <!-- Section Title -->
            <div class="container section-title" data-aos="fade-up">
                <h2>About</h2>
                <p><span>About</span> <span class="description-title">Planedge</span></p>
            </div><!-- End Section Title -->

            <div class="container">

                <div class="row gy-3">

                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                        <img loading="lazy" src="https://bootstrapmade.com/content/demo/BizLand/assets/img/about.jpg"
                            alt="" class="img-fluid">
                    </div>

                    <div class="col-lg-6 d-flex flex-column justify-content-center" data-aos="fade-up"
                        data-aos-delay="200">
                        <div class="about-content ps-0 ps-lg-3">
                            <h3>Building Better, Smarter Landmarks</h3>
                            <p class="fst-italic">
                                Planedge is a multidisciplinary Project Management Consultancy committed to building
                                better, smarter, and more efficient landmarks. With a philosophy rooted in People,
                                Process, and Purpose, Planedge delivers excellence across every stage of the project
                                lifecycle.
                            </p>
                            <ul>
                                <li>
                                    <i class="bi bi-eye-fill"></i>
                                    <div>
                                        <h4>Vision</h4>
                                        <p>
                                            To become one of the top global project management firms, creating
                                            engineering marvels that shape skylines.
                                        </p>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-bullseye"></i>
                                    <div>
                                        <h4> Mission</h4>
                                        <p>
                                            To enable world-class structures through innovative solutions, technological
                                            excellence, and discipline-led project management.
                                        </p>
                                    </div>
                                </li>
                            </ul>

                        </div>

                    </div>
                </div>

            </div>

        </section><!-- /About Section -->



        <!-- Stats Section -->
        <section id="Expertise" class="stats section">
            <div class="container section-title aos-init aos-animate" data-aos="fade-up">

                <p><span>Our Areas Of </span> <span class="description-title">Expertise</span></p>
            </div>
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4">

                    <div class="col-lg-4 col-md-6">
                        <a href="https://www.planedge.in/" target="_blank"
                            class="d-flex flex-column align-items-center text-decoration-none">
                            <i class="bi bi-building fs-2"></i>
                            <div class="stats-item text-center"> 
                                <p>Commercial</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <a href="https://www.planedge.in/" target="_blank"
                            class="d-flex flex-column align-items-center text-decoration-none">
                            <i class="bi bi-house-door fs-2"></i>
                            <div class="stats-item text-center"> 
                                <p>Residential</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <a href="https://www.planedge.in/" target="_blank"
                            class="d-flex flex-column align-items-center text-decoration-none">
                            <i class="bi bi-bank fs-2"></i>
                            <div class="stats-item text-center"> 
                                <p>Institutions</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <a href="https://www.planedge.in/" target="_blank"
                            class="d-flex flex-column align-items-center text-decoration-none">
                            <i class="bi bi-briefcase fs-2"></i>
                            <div class="stats-item text-center"> 
                                <p>Corporate</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <a href="https://www.planedge.in/" target="_blank"
                            class="d-flex flex-column align-items-center text-decoration-none">
                            <i class="bi bi-cup-hot fs-2"></i>
                            <div class="stats-item text-center"> 
                                <p>Hospitality</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <a href="https://www.planedge.in/" target="_blank"
                            class="d-flex flex-column align-items-center text-decoration-none">
                            <i class="bi bi-shield-check fs-2"></i>
                            <div class="stats-item text-center"> 
                                <p>Government</p>
                            </div>
                        </a>
                    </div>

                </div>


            </div>

        </section>
        <section id="achievements" class="contact section">

            <!-- Section Title -->
            <div class="container section-title" data-aos="fade-up">
                <h2>Achievements</h2>
                <p><span>Certifications & </span> <span class="description-title">Accreditations</span></p>
            </div><!-- End Section Title -->

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4">

                    <div class="col-lg-12">

                        <div class="text-center">
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <span class="badge bg-primary p-3">ISO Certified</span>
                                <span class="badge bg-success p-3">PMI Member</span>
                                <span class="badge bg-info p-3">Crisil Rated</span>
                                <span class="badge bg-warning p-3">Chartered Engineer</span>
                                <span class="badge bg-dark p-3">MCAA Accredited</span>
                            </div>
                        </div>


                    </div>
                </div>


            </div>

            </div>

        </section>

        <section id="contact" class="contact section">

            <!-- Section Title -->
            <div class="container section-title" data-aos="fade-up">
                <h2>Contact</h2>
                <p><span>Need Help?</span> <span class="description-title">Contact Us</span></p>
            </div><!-- End Section Title -->

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4">

                    <div class="col-lg-5">

                        <div class="p-4 bg-white rounded shadow-sm">
                            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="200">
                                <i class="bi bi-geo-alt flex-shrink-0"></i>
                                <div>
                                    <h3>Address</h3>
                                    <p>6th Floor, Gagan Uno, Dhole Patil Road, Pune, Maharashtra 411001</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="300">
                                <i class="bi bi-telephone flex-shrink-0"></i>
                                <div>
                                    <h3>Call Us</h3>
                                    <p>+91 888-888-7891 / +91 988-103-3222</p>
                                    <p>020 2616 6466</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="400">
                                <i class="bi bi-envelope flex-shrink-0"></i>
                                <div>
                                    <h3>Email Us</h3>
                                    <p>career@planedge.in</p>
                                    <p>bd@planedge.in</p>
                                </div>
                            </div><!-- End Info Item -->



                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="p-4 bg-white rounded shadow-sm">
                            <h4 class="mb-4">Need Support or System Access?</h4>
                            <p class="mb-4">For bill tracking support, system access requests, or general inquiries,
                                please contact us:</p>

                            <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle me-2"></i>Bill Tracker Support</h5>
                                <p class="mb-0">For assistance with the bill tracking system, please email:
                                    <strong>bd@planedge.in</strong>
                                </p>
                            </div>

                            <div class="mt-4">
                                <h5>Office Hours</h5>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM IST<br>Saturday: 9:00 AM - 1:00 PM IST</p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </section>

    </main>

    <footer id="footer" class="footer">


        <div class="container copyright text-center mt-4">
            <p>© <span>Copyright</span> <strong class="px-1 sitename">Vertscend Automation Private Limited
</strong> <span>All
                    Rights Reserved</span>
            </p>
            <div class="credits">
                Designed and developed by <a href="https://vertscend.in/" target="_blank">Vertscend Automation Pvt.
                    Ltd</a>
            </div>
        </div>

    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script> 

    <script>
        (function() {
            "use strict";

            // Preloader
            const preloader = document.querySelector('#preloader');
            if (preloader) {
                window.addEventListener('load', () => {
                    preloader.remove();
                });
            }

            // Mobile nav toggle
            const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

            function mobileNavToogle() {
                document.querySelector('body').classList.toggle('mobile-nav-active');
                mobileNavToggleBtn.classList.toggle('bi-list');
                mobileNavToggleBtn.classList.toggle('bi-x');
            }
            mobileNavToggleBtn.addEventListener('click', mobileNavToogle);

            // Hide mobile nav on same-page links
            document.querySelectorAll('#navmenu a').forEach(navmenu => {
                navmenu.addEventListener('click', () => {
                    if (document.querySelector('.mobile-nav-active')) {
                        mobileNavToogle();
                    }
                });
            });

            // Scroll top button
            let scrollTop = document.querySelector('.scroll-top');

            function toggleScrollTop() {
                if (scrollTop) {
                    window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
                }
            }
            scrollTop.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            window.addEventListener('load', toggleScrollTop);
            document.addEventListener('scroll', toggleScrollTop);

            // AOS Animation
            function aosInit() {
                AOS.init({
                    duration: 600,
                    easing: 'ease-in-out',
                    once: true,
                    mirror: false
                });
            }
            window.addEventListener('load', aosInit);

            // Simple counter animation
            function animateCounter(element, target, duration = 2000) {
                let start = 0;
                const increment = target / (duration / 16); // 60fps
                const timer = setInterval(() => {
                    start += increment;
                    if (start >= target) {
                        element.textContent = target;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(start);
                    }
                }, 16);
            }

            // Initialize counters when they come into view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const target = parseInt(counter.getAttribute('data-target'));
                        animateCounter(counter, target, 2000);
                        observer.unobserve(counter); // Stop observing after animation
                    }
                });
            }, {
                threshold: 0.5, // Start when 50% of element is visible
                rootMargin: '0px 0px -50px 0px' // Trigger slightly before element is in view
            });

            // Observe all counters
            document.querySelectorAll('.counter').forEach(counter => {
                observer.observe(counter);
            });

            // Also trigger for AOS animation
            document.addEventListener('aos:in', function(e) {
                if (e.target.querySelector('.counter')) {
                    e.target.querySelectorAll('.counter').forEach(counter => {
                        if (parseInt(counter.textContent) === 0) { // Only animate if not already animated
                            const target = parseInt(counter.getAttribute('data-target'));
                            animateCounter(counter, target, 2000);
                        }
                    });
                }
            });

        })();
    </script>

</body>

</html>