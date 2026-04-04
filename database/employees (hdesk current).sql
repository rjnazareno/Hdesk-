-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 04, 2026 at 12:05 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u816220874_ticketing`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `personal_email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `official_sched` int(11) DEFAULT NULL,
  `role` enum('employee','supervisor','admin','internal','internal_hr','internal_it','superadmin') DEFAULT 'employee',
  `admin_rights_hdesk` enum('it','hr','superadmin') DEFAULT NULL COMMENT 'HelpDesk admin rights: it, hr, superadmin, or null for regular employee',
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `username`, `email`, `personal_email`, `password`, `fname`, `lname`, `company`, `position`, `contact`, `official_sched`, `role`, `admin_rights_hdesk`, `status`, `profile_picture`, `fcm_token`, `profile_image`, `created_at`) VALUES
(1, '1053', 'Abi.Basilio', NULL, NULL, 'entry_abi', 'Abigail', 'Basilio', 'RSO', 'Content Writer & Instructional Designer', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(2, '1039', 'Abi.Bunda', 'Abigael.Bunda@stoddarts.com.au', NULL, 'Acb9293!!', 'Abigael', 'Bunda', 'Stoddart Group', 'Architectural Detailer', '09277375956', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(3, '58', 'adonis.jabinal', 'adonis.jabinal@bugardi.com.au', 'donjabinal@gmail.com', 'atleast6', 'Adonis Del Mundo', 'Jabinal', 'Bugardi', 'Project Coordinator', '175-092-008-000', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(4, '5', 'aizel.castro', 'aizel.castro@entrygroup.com.au', 'aizel.castro01@gmail.com', '123789', 'Aizel Santos', 'Castro', 'Entry Education', 'Student Support - Marking', '0926 215 0722', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(5, '33', 'aldwin.lozano', 'aldwinjohn.lozano@entrygroup.com.au', 'imaj.lozano@gmail.com', 'Dumbapples@0417!', 'Aldwin John', 'Arceo Lozano', 'Entry Education', 'Sales New Student Enquiries', '0949 369 7174', 7, 'employee', NULL, 'active', 'profile_33_1751407949.jpg', NULL, NULL, '2026-02-11 06:50:18'),
(6, '1024', 'alex.tayao', 'alextayao23@gmail.com', '', 'P@xxword!', 'Alexander', 'Tayao', 'Boss Electrical', 'Draftsman', '+639063177751', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(7, '77', 'Alfie.Guillermo', 'alfie.guillermo0219@gmail.com', 'alfie.guillermo0219@gmail.com', '123456', 'Alfie', 'Guillermo', 'TTS', 'Estimator', '09773853986', 9, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(8, '20', 'alfred.ocampo', 'Alfred@entrygroup.com.au', 'derfla61@gmail.com', '123456', 'Alfred Naguit', 'Ocampo', 'Entry Education', 'Conveyancing Client Support', '0963 256 7621', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(9, '56', 'allen.capati', 'allen.capati@entrygroup.com.au', 'allen.capati95@gmail.com', '042316!', 'Allen Sobrepeña', 'Capati', 'Entry Education', 'Student Support - Marking', '468-497-485-000', 4, 'employee', NULL, 'active', 'profile_56_1751353871.png', NULL, NULL, '2026-02-11 06:50:18'),
(10, '52', 'althea.makabenta', 'document.control@bugardi.com.au', 'atansingcomakabenta@yahoo.com', '123456', 'Althea Tansingco', 'Makabenta', 'Bugardi', 'Document Controller', '165-661-778-000', 6, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(11, '1043', 'Alvir.Tayag', 'alvirrivera0628@gmail.com', NULL, 'denning_alvir', 'Alvir', 'Rivera', 'Denning & Associates', 'Administrative Assistant - Tax Support', '09171487721', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(12, '25', 'analiza.gatbonton', 'analiza.gatbonton@entrygroup.com.au', 'analizagatbonton05@gmail.com', 'AnaLiza31!', 'Analiza Taloban ', 'Gatbonton', 'Entry Education', 'Student Support - Marking', '0961 820 1167', 14, 'employee', NULL, 'active', 'profile_25_1751353994.JPG', NULL, NULL, '2026-02-11 06:50:20'),
(13, '57', 'angelica.estanio', 'angelica.estanio@entrygroup.com.au', 'angelica.estanio4@gmail.com', '096664', 'Angelica Rosario', 'Estanio', 'Entry Education', 'Student Support - Marking', '09666483316', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(14, '67', 'apryl.pasion', 'apryl.pasion@bugardi.com.au', 'aprylpolicarpio@gmail.com', 'apyap25!', 'Apryl Pasion ', 'Yap', 'Bugardi', 'Recruitment Mobilization Officer', 'TO FOLLOW', 6, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:23'),
(15, '12', 'beverly.gatbonton', 'beverly.gatbonton@entrygroup.com.au', 'beverlygatbonton29@gmail.com', '$Richbev29', 'Beverly Taloban ', 'Gatbonton', 'Entry Education', 'Team Leader Sales - New Student Enquiries', '0920 403 7997', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(16, '1048', 'Bon.Patacsil', 'IT@resourcestaff.com.ph', NULL, 'Fidelis14!!', 'Bon Febryx', 'Patacsil', 'RSS', 'IT Support Specialist', '09776867141', NULL, 'internal', 'it', 'active', NULL, 'cou_o-sJKtKrBGQVpZJzQZ:APA91bFCREy7Z-uUiHCNi0qyCshz2DwGS2fRJ28RT42XEJ2zwBEF1_48ST1efXCmUObKNfyqq9iUkPt1czY7nn6P8E2mW1B5CIwmgdhxfqWOHOmOBwWAWoY', NULL, '2026-02-11 06:50:21'),
(17, '78', 'Brittany.Yulo', 'yulobrittany@gmail.com', 'brittany@trswa.net.au', 'Trswabrit', 'Brittany', 'Yulo', 'TRSWA', 'Commercial Assistant Administrator', '09201337394', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:23'),
(18, '1032', 'Carl.Tupaz', 'tupaz.carldave@gmail.com', NULL, 'Carldave08!', 'Carl', 'Tupaz', 'Eastman Electrical', 'Electrical Estimator', '09276009751', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:23'),
(19, '1041', 'Cesar.Cochon', 'Cesar.Cochon@stoddarts.com.au', NULL, '123456', 'Cesar', 'Cochon', 'Stoddart Group', 'Architectural Detailer', '09086320321', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(20, '41', 'charisma.platero', 'charisma.platero@gmail.com', 'charisma.platero@gmail.com', '123456', 'Ma. Charisma S.', 'Platero', 'Fratelli Homes', 'Estimator', '09566998110', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(21, '1055', 'Cherry.Gonzales', NULL, NULL, '123456', 'Cherry', 'Gonzales', 'RSO', 'Administrative Assistant', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(22, '1029', 'Chloedean.Flores', 'deaaan920@gmail.com', NULL, '123456', 'Chloedean', 'Flores', 'Hammerhire', 'Genereal Service Administrator', '09359157318', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(23, '53', 'christian.mar', 'christianmar673@gmail.com', 'christianmar673@gmail.com', 'Apple@123!', 'Christian Nioda', 'Mar', 'Entry Education', 'Sales New Student Enquiries', '387-307-553-000', 13, 'employee', NULL, 'active', 'profile_53_1751526003.jpg', NULL, NULL, '2026-02-11 06:50:21'),
(24, '68', 'christine.angeles', 'christinekhlaryss@gmail.com', 'christinekhlaryss@gmail.com', 'CKAngeles1997.', 'Christine Khlaryss', 'Angeles', 'Denning', 'Tax Accountant', '351-635-569-000', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(25, '36', 'denver.castillano', 'dcstudio.creative@gmail.com', 'dcstudio.creative@gmail.com', '123456', 'Denver Orlanda', 'Castillano', 'DNA Furniture & Cabinets', 'Draftsman', '0991 933 2312', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(26, '18', 'edith.mataga', 'Edith@entrygroup.com.au', 'edghie03@gmail.com', '123456', 'Edith', 'David Mataga', 'Entry Education', 'Sales New Student Enquiries', '0935 563 9451', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(27, '1044', 'Efrael.Agkis', 'efraelagkis0211@gmail.com', NULL, '123456', 'Efrael', 'Agkis', 'Leading Cranes', 'Structural/Mechanical Draftsman', '09277112098', NULL, 'employee', NULL, 'active', 'profile_1044_1768168549.png', NULL, NULL, '2026-02-11 06:50:17'),
(28, '1037', 'Elmer.Gagote', 'Elmer.Gagote@stoddarts.com.au', NULL, '123456', 'Elmer', 'Gagote', 'Stoddart Group', 'Architectural Detailer', '09083100906', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(29, '24', 'elritz.crisanto', 'elritz.crisanto@entrygroup.com.au', 'ritztiong@gmail.com', '123456', 'Elritz', 'Crisanto', 'Entry Education', 'Student Support - Marking', '0961 289 3349', 2, 'employee', NULL, 'active', 'profile_24_1751354386.png', NULL, NULL, '2026-02-11 06:50:19'),
(30, '1040', 'Eri.Otaka', 'Eri.Otaka@stoddarts.com.au', NULL, '123456', 'Eri', 'Otaka', 'Stoddart Group', 'Architectural Detailer', '09663190631', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(31, '30', 'erika.pineda', 'erika.seriosa@entrygroup.com.au', 'rickzseriosa@gmail.com', 'Erse@1023', 'Erika Seriosa ', 'Pineda', 'Entry Education', 'Student Support - Marking', '0926 355 6900', 13, 'employee', NULL, 'active', 'profile_30_1751450142.png', NULL, NULL, '2026-02-11 06:50:22'),
(32, '15', 'evanel.navalon', 'evanel.navalon@entrygroup.com.au', 'evanelnavalon@gmail.com', '033096', 'Evanel', ' Navalon', 'Entry Education', 'Technical Student Support', '0946 882 2198', 6, 'employee', NULL, 'active', 'profile_15_1752052633.png', NULL, NULL, '2026-02-11 06:50:17'),
(33, '50', 'francis.bondoc', 'francis.bondoc22@gmail.com', 'francis.bondoc22@gmail.com', '123456', 'Francis Eugene Aguhayon', 'Bondoc', 'Entry Education', 'Draftsman', '629-683-416-000', 16, 'employee', NULL, 'active', 'profile_50_1751860113.jpg', NULL, NULL, '2026-02-11 06:50:18'),
(34, '7', 'francis.fernandez', 'francis@entrygroup.com.au', 'francis2208@gmail.com', 'RSS@69', 'Francis Emmanuel Veloso', 'Fernandez', 'Entry Education', 'Student Support - Marking Team Leader', '0967 201 4330', 13, 'employee', NULL, 'active', 'profile_7_1751353606.png', NULL, NULL, '2026-02-11 06:50:20'),
(35, '26', 'franklin.pabillano', 'estimating@empirewestelectrical.com.au', 'frankpabillano@gmail.com', '123456', 'Franklin Roos', 'Cinco Pabillano', 'onn one', 'Electrical Estimator', '0961 498 0228', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(36, '82', 'Gabriel.Capiral', 'capiralgabriel@gmail.com', NULL, 'Zappa_Gab08', 'Gabriel', 'Capiral', 'Fratelli Homes', '', '', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(37, '1047', 'Gavin.James', 'gavin@integrityit.co.nz', NULL, 'KH8yt%4%4', 'Gavin', 'James', 'RSO', 'IT administrator', 'N/A', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(38, '47', 'glory.balderas', 'Glory@fratellihomeswa.com.au', 'gloryannbalderas@gmail.com', '123456', 'Glory Ann', 'Balderas', 'Fratelli Homes', 'Estimator', '0981 107 2866', 4, 'employee', NULL, 'active', 'profile_47_1751848664.png', NULL, NULL, '2026-02-11 06:50:18'),
(39, '66', 'godwin.ocampo ', 'tgodbtg04@gmail.com', 'tgodbtg04@gmail.com', '123456', 'Godwin', 'Ocampo', 'Denning & Associates', 'Tax Accountant', '09603985500', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(40, '1057', 'hdesk.test', 'hdesk.test@gmail.com', NULL, '123456', 'Hdesk', 'test', 'RSO', 'Architectural Detailer', '0905 219 2943', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(41, '16', 'ian.aguilar', 'ian.aguilar@entrygroup.com.au', 'iammycovital@gmail.com', '123456', 'Ian Myco', 'Aguilar', 'Entry Education', 'Student Support - Marking', '0976 198 9787', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(42, '46', 'ivy.nuñez', 'ivynunez26@gmail.com', 'ivynunez26@gmail.com', 'Lois0707@', 'Ivy', 'Nuñez', 'Entry Education', 'Student Support - Marking', 'N/A', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(43, '80', 'Jae.Fernandez', 'mjae.fernandez@yahoo.com', 'mjae.fernandez@yahoo.com', 'Fernandez@1215', 'Marianne Jae ', 'Fernandez', 'Miller\'s Roofing', 'Administrative Assistant', '09610912234', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(44, '1034', 'Jairus.Ignacio', 'jairusignaciogomez@gmail.com', NULL, '123456', 'Jairus', 'Ignacio', 'Quality Roofing', 'Digital Marketing', '09171553644', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(45, '60', 'janeth.solayao', 'janeth.solayao@entrygroup.com.au', 'janethsolayao32@gmail.com', '123456', 'Janeth Sedon', 'Solayao', 'Entry Education', 'Student Support - Marking', 'TO FOLLOW', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(46, '1038', 'Jay.Asuncion', 'Jay.Asuncion@stoddarts.com.au', NULL, '123456', 'Jay Kimbert', 'Asuncion', '', 'Architectural Detailer', '09192566802', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(47, '55', 'jeffry.macapagal', 'jeff.macapagal017@gmail.com', 'jeff.macapagal017@gmail.com', '123456', 'Jeffry', 'Macapagal', 'TRSWA', 'Operations Administrator', '09984097709', 3, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(48, '21', 'jennifer.trinidad', 'jennifer.trinidad@entrygroup.com.au', 'jennifertrinidad0103@gmail.com', '123456', 'Jennifer', 'Trinidad', 'Entry Education', 'Student Support - Marking', '0928 225 5869', 13, 'employee', NULL, 'active', 'profile_21_1752047143.png', NULL, NULL, '2026-02-11 06:50:22'),
(49, '1056', 'Jerome.Espinosa', NULL, NULL, 'f&d_jerome', 'Jerome', 'Espinosa', 'RSO', 'Administrative Assistant', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(50, '31', 'jhunel.samodio', 'jhunelcarlo.samodio@entrygroup.com.au', 'gun.lazuli@gmail.com', 'mamamo0514', 'Jhunel Carlo Traifalgar ', 'Samodio', 'Entry Education', 'Student Support - Marking', '0995 483 5711', 13, 'employee', NULL, 'active', 'profile_31_1751598183.png', NULL, NULL, '2026-02-11 06:50:22'),
(51, '14', 'Jillian.Agas', 'jillian.agas@entrygroup.com.au', 'jjjillian052089@gmail.com', 'Sesshou#052089', 'Jillian', 'Agas', 'Entry Education', 'Technical Student Support', '0915 546 4289', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(52, '70', 'jm.briones', 'jamenabriones14@gmail.com', 'jamenabriones14@gmail.com', '123456', 'John Michael Comprado', 'Briones', 'TRSWA', 'Commercial Estimator', '620-743-947-000', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(53, '4', 'joel.alimurong', 'Joel.Alimurong@entrygroup.com.au', 'jhei.el1768@gmail.com', '261768', 'Joel Lusung', 'Alimurong', 'Entry Education', 'Student Support - Marking', '0966 539 4550', 13, 'employee', NULL, 'active', 'profile_4_1749606383.jpeg', NULL, NULL, '2026-02-11 06:50:17'),
(54, '29', 'johana.gueco', 'johanarose.gueco@entrygroup.com.au', 'johanagueco@gmail.com', 'Rebisco21*', 'Johana Rose Perez ', 'Gueco', 'Entry Education', 'Student Support - Marking', '0906 213 7926', 13, 'employee', NULL, 'active', 'profile_29_1751450128.png', NULL, NULL, '2026-02-11 06:50:20'),
(55, '74', 'John.Alvarez', 'johnalvarez930@gmail.com', 'johnalvarez930@gmail.com', '123456', 'John Bryan', 'Alvarez', 'TTS', 'Operations Admin', NULL, 9, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(56, '79', 'John.Sanoza', 'sanozajohn@gmail.com', 'sanozajohn@gmail.com', '123456', 'John ', 'Sanoza', 'Viper', 'Senior Full Stack Developer ', '', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(57, '1005', 'johsua.dimla', 'johsua.dimla1986@gmail.com', 'johsua.dimla1986@gmail.com', '123456', 'Johsua Torninos', 'Dimla', 'RSS', 'Facilities and Admin Support', '09292741102', 7, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(58, '86', 'Jonas.Delacruz', '', NULL, '123456', 'Jonas', 'Dela Cruz', 'Alpha Industry', 'Draftsman', '', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(59, '1028', 'Joseph.David', 'josephdavid521@gmail.com', NULL, 'david042519', 'Joseph', 'David', 'Boiso\'s Electrical', 'Electrical Estimator', '09355156272', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(60, '83', 'Joshua.Manalili', 'jshmnl1528@gmail.com', NULL, 'Myamarra@13', 'Joshua', 'Manalili', 'BUSQLD', 'Network Controller', '09616126980', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(61, '59', 'joshwea.monis', 'joshwea.monis@entrygroup.com.au', 'mjoshwea@gmail.com', '942103', 'Joshwea Mercado', 'Monis', 'Entry Education', 'Student Support - Marking', '332-760-833-000', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(62, '49', 'julie.maclang', 'macjulg08@gmail.com', 'macjulg08@gmail.com', '100284', 'Julie Anne', 'Maclang', 'Entry Education', 'Student Support - Marking', 'N/A', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(63, '1026', 'karen.belangel', 'tofollow@gmail2.com', NULL, 'zeels_karen', 'Karen', 'Belangel', 'ZeelKitchens', 'Executive Assistant', '', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(64, '1042', 'Karla.Arceo', 'jkzarceo@gmail.com', NULL, 'harryS143!', 'Karla', 'Arceo', 'RSO', 'Admin Assistant', '09556515833', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(65, '1025', 'kim.dacquil', 'Tofollow@gmail.com2', NULL, 'OnTime_Kim1225!', 'Kimberly', 'Dacquil', 'Onn1', 'Accounts Administrator', '', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(66, '1009', 'Kiras001', 'rjmanago@gmail.com', 'rjmanago@gmail.com', 'vosfows13', 'Resty James', 'Nazareno', 'RSS', 'IT Intern', '09763659773', 4, 'internal', 'superadmin', 'active', 'profile_1009_1749770448.jpeg', 'cou_o-sJKtKrBGQVpZJzQZ:APA91bFCREy7Z-uUiHCNi0qyCshz2DwGS2fRJ28RT42XEJ2zwBEF1_48ST1efXCmUObKNfyqq9iUkPt1czY7nn6P8E2mW1B5CIwmgdhxfqWOHOmOBwWAWoY', NULL, '2026-02-11 06:50:21'),
(67, '27', 'kristian.bansil', 'ITsupport@mtunderground.com', 'ian_pudz@icloud.com', 'Anew903west889!', 'Kristian David', 'Bansil', 'Maintenance Tech', 'Web Developer / Admin & IT Support', '0939 905 0288', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(68, '1031', 'Kryssa.Gabatino', 'kryssagabatino17@gmail.com', NULL, '072221Kryssa!!!', 'Kryssa', 'Gabatino', 'Ford and Doonan', 'Administrative Assistant', '09399348559', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(69, '87', 'lance.libo', 'lancejomerlibo@gmail.com', NULL, '123456', 'Lance', 'Libo', 'Viper', 'Front-end Flutter Developer', '0951106693', 5, 'employee', NULL, 'active', 'profile_87_1753088590.png', NULL, NULL, '2026-02-11 06:50:21'),
(70, '65', 'Lester.nuñeza ', 'lester.nuneza@bugardi.com.au', 'lesternuneza@gmail.com', '123456', 'Dou Lester Sabando', 'Nuñeza ', 'Bugardi', 'HSEQ Assistant Manager', '+639618436508', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(71, '28', 'louis.austria', 'louis.austria@entrygroup.com.au', 'louisaustria0@gmail.com', 'Bri+yulo123', 'Louis Fernand', 'Austria', 'Entry Group', 'Graphic Designer', '0998 423 4020', 6, 'employee', NULL, 'active', 'profile_69bcf20eefa63.jpeg', NULL, NULL, '2026-02-11 06:50:18'),
(72, '43', 'lovelaine.celeste', 'Lovelaine.Celeste@entrygroup.com.au', 'lovelaineceleste@yahoo.com', 'RSS2024', 'Lovelaine', 'Celeste', 'Entry Education', 'Sales New Student Enquiries', '09761349029', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(73, '1049', 'Mari.Garcia', 'mariareynaldagarcia0312@gmail.com', NULL, 'rss_mari', 'Marirey', 'Garcia', '', 'Sales Support', '', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(74, '37', 'marnie.catalogo', 'marniecatalogo99@gmail.com', 'marniecatalogo99@gmail.com', '112620', 'Marnie Perez ', 'Catalogo', 'Entry Education', 'Technical Student Support', '0905 453 8974', 13, 'employee', NULL, 'active', 'profile_37_1769514812.jpg', NULL, NULL, '2026-02-11 06:50:19'),
(75, '11', 'mary.soriano', 'mary@entrygroup.com.au', 'habibisoriano@yahoo.com', '022714', 'Mary Ann Vallejos ', 'Soriano', 'Entry Education', 'Sales New Student Enquiries', '0906 255 2990', 13, 'employee', NULL, 'active', 'profile_11_1749624970.jpeg', NULL, NULL, '2026-02-11 06:50:22'),
(76, '1054', 'Michelle.Calma', 'Michellepcalma@marleeresources.com.au', NULL, 'marlee_michelle', 'Michelle', 'Calma', 'Marlee Resources', 'Operations & Mobilisation Coordinator', '09913274893', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(77, '1045', 'Mikaella.Capiral', 'capiralmikaella@gmail.com', NULL, 'mikaella_capiral', 'Mikaella', 'Capiral', 'RSO', 'Virtual Assistant', '09169062104', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(78, '72', 'Milbert.Sambile', 'milbert@millersroofing.com.au', 'milbert.sambile@gmail.com', '123456', 'Milbert ', 'Sambile', 'Miller\'s Roofing', 'Estimator', '09663995493', 4, 'employee', NULL, 'active', 'profile_72_1751614448.png', NULL, NULL, '2026-02-11 06:50:22'),
(79, '1033', 'Mylene.Torres', 'mylene.dauz@yahoo.com', NULL, '123456', 'Mylene', 'Torres', '', 'Accounts Payable Officer', '09202575993', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(80, '1036', 'Nash.Guevarra', 'Jeiel.Guevarra@stoddarts.com.au', NULL, '123456', 'Jeiel Nash', 'Guevarra', '', 'Architectural Detailer', '09760645925', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(81, '1002', 'neil.costelloe', 'Neil.Costelloe@resourcestaff.com.ph', 'neilcosetelloe@gmail.com', '123456', 'Neil Anthony', 'Costelloe', 'RSS', 'General Manager', NULL, 5, 'internal', 'superadmin', 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(82, '35', 'nika.bacongallo', 'nika.bacongallo@gmail.com', 'nika.bacongallo@gmail.com', 'Fpowl8a8dtun!', 'Nika', 'Bacongallo', 'Entry Education', 'Student Support - Marking', '0919 263 0516', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(83, '63', 'Nina.dollentes', 'nina.dollentes@entrygroup.com.au', 'marianinadollentes@gmail.com', '347243', 'Maria Ñina', 'Dollentes Cruz', 'Entry Education', 'Accountant', '09122208976', 4, 'employee', NULL, 'active', 'profile_63_1752186681.jpeg', NULL, NULL, '2026-02-11 06:50:19'),
(84, '75', 'Oliva.Bautista', 'olivesantos.bautista@gmail.com', 'olivesantos.bautista@gmail.com', 'ttsbuilt20', 'Oliva', 'Bautista', 'TTS', 'Operations Admin', '', 9, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(85, '1007', 'Peach.Herrera', 'herrerafelicci@gmail.com', 'herrerafelicci@gmail.com', 'seasalt', 'Felicci Alejan Mariesther', 'Herrera', 'RSS', 'Admin', '09982971494', 4, 'internal', 'hr', 'active', 'profile_1007.jpg', 'cou_o-sJKtKrBGQVpZJzQZ:APA91bFCREy7Z-uUiHCNi0qyCshz2DwGS2fRJ28RT42XEJ2zwBEF1_48ST1efXCmUObKNfyqq9iUkPt1czY7nn6P8E2mW1B5CIwmgdhxfqWOHOmOBwWAWoY', NULL, '2026-02-11 06:50:20'),
(86, '1027', 'rebecca.david', 'rebeccad.david@gmail.com', NULL, '123456', 'Rebecca', 'David', 'Venaso Selections', 'Bookkeeper', '09177047621', 16, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(87, '3', 'renalyn.josafat', 'reny@entrygroup.com.au', 'rajosafat.cca@gmail.com', '123456', 'Renalyn Abamo', 'Josafat', 'Entry Education', 'Student Support Mentoring', '0939 245 6150', 12, 'employee', NULL, 'active', 'profile_3_1752052468.png', NULL, NULL, '2026-02-11 06:50:21'),
(88, '17', 'reneeca.benalla', 'Reneeca@entrygroup.com.au', 'reneeca.benalla@gmail.com', '123456', 'Reneeca Villapaña', ' Benalla', 'Entry Education', 'Content Writer & Instructional Designer', '0909 204 3758', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(89, '6', 'reymark.colis', 'bryan@entrygroup.com.au', 'reymarkbryancolis@gmail.com', 'iodine86236', 'Reymark Bryan Silvano', 'Colis', 'Entry Education', 'Technical Student Support - Team Leader', '0927 014 4692', 8, 'employee', NULL, 'active', 'profile_6_1751354099.png', NULL, NULL, '2026-02-11 06:50:19'),
(90, '10', 'rhegene.ronquillo', 'reggie@entrygroup.com.au', 'rronquillo0727@gmail.com', '123456', 'Rhegene', 'Ronquillo', 'Entry Education', 'Technical Student Support', '0916 936 6370', 4, 'employee', NULL, 'active', 'profile_10_1751353416.JPG', NULL, NULL, '2026-02-11 06:50:22'),
(91, '1052', 'Ria.Agabin', NULL, NULL, 'eastman_ria', 'Ria', 'Agabin', 'RSO', 'Virtual Executive Assistant', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(92, '1051', 'Ria.Santiago', NULL, NULL, 'fairview_ria', 'Ria', 'Santiago', 'RSO', 'Accounts Payable and Admin Officer', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(93, '61', 'rj.singh', 'rvschk@gmail.com', 'rvschk@gmail.com', '123456', 'Ray Jinder Villena', 'Singh', 'Rowland Plumbing & Gas', 'Executive Assistant', '350-760-267-000', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(94, '1035', 'RJ.Tiglao', 'jaeliz0529@gmail.com', NULL, 'bugardi_ryanjae', 'Ryan Jae', 'Tiglao', 'Bugardi', 'IT/Data Analyst', '09284652540', 5, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(95, '1004', 'rj.tolomia', 'Rica.Tolomia@resourcestaff.com.ph', 'Rica.Tolomia@resourcestaff.com.ph', '123456', 'Rica Joy Viray', 'Tolomia', 'RSS', 'TA/HR Specialist', '0917 389 7962', 4, 'internal', 'hr', 'active', 'profile_1004_1751339641.png', NULL, NULL, '2026-02-11 06:50:22'),
(96, '13', 'rogelio.malinao', 'rogelio.malinao@entrygroup.com.au', 'rogelio.malinao@gmail.com', 'Entry_Rogelio143', 'Rogelio', ' Malinao Jr', 'Entry Education', 'Student Support - Marking', '0976 212 6539', 2, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(97, '84', 'Roi.Pangilinan', 'roidane.pangilinan@gmail.com', NULL, 'Akopanaman24!!!', 'Roi Dane', 'Pangilinan', 'BUSQLD', ' Network Controller', '', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(98, '1030', 'Ron.Cueto', 'ron.cueto@bugardi.com.au', NULL, '123456', 'Ron', 'Cueto', 'Bugardi', 'Design Coordinator', '09770931165', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(99, '89', 'roxanne.tulaytay', 'roxanne.tulaytay@gmail.com', NULL, '123456', 'Roxanne', 'Tulaytay', 'Hammerhire', 'Accounts Payable Officer', '09668276474', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:23'),
(100, '1046', 'Russell.Bautista', 'russellrudolfhbautista@gmail.com', NULL, 'IAMLEGEND123', 'Russell', 'Bautista', 'RSO', 'Network Controller', '09555642628', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(101, '73', 'Ryan.David', 'davidryarwin@gmail.com', 'davidryarwin@gmail.com', '123456', 'Ryan Arwin', 'David', 'TTS', 'Operations Admin', NULL, 9, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(102, '38', 'ryan.patrimonio', 'rexryanpatrimonio@gmail.com', 'rexryanpatrimonio@gmail.com', '123456', 'Rex Ryan', 'Patrimonio', 'Entry Education', 'Technical Student Support', '0916 189 2527', 7, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(103, '76', 'Sarah.Caraan', 'sarahcaraan31@gmail.com', 'sarahcaraan31@gmail.com', '123456', 'Sarah', 'Caraan', 'TTS', 'Operations Admin', '', 9, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:19'),
(104, '22', 'sean.mendoza', 'sean.mendoza@entrygroup.com.au', 'mendozaseanjustine@gmail.com', '123456', 'Sean Justine', 'Mendoza', 'Entry Education', 'Student Support - Marking', '0977 019 9064', 13, 'employee', NULL, 'active', 'profile_22_1751353445.png', NULL, NULL, '2026-02-11 06:50:21'),
(105, '2', 'shaina.dela cruz', 'shainadc86@gmail.com', 'shainadc86@gmail.com', '123456', 'Shaina', 'Dela Cruz', 'Entry Education', 'Student Support Mentoring', '0935 766 5039', 5, 'employee', NULL, 'active', 'profile_2_1749605234.jpeg', NULL, NULL, '2026-02-11 06:50:19'),
(106, '81', 'Sherry.Patawaran', 'marketing@hammerhire.com.au', 'sherryrosepatawaran@gmail.com', '123456', 'Sherry Rose Ann', 'Patawaran', 'HammerHire', 'Marketing Coordinator', '09398522815', 8, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(107, '9', 'shigeru.centina', 'Shigeru.Otsuka@entrygroup.com.au', 'shigeruslayer12345@gmail.com', 'P@ssw0rd01', 'Shigeru', 'Otsuka', 'Entry Education', 'Instructional Designer - Technical Specialist', '0939 286 1648', 4, 'employee', NULL, 'active', 'profile_9_1751354113.jpg', NULL, NULL, '2026-02-11 06:50:21'),
(108, '62', 'shirmiley.quizon', 'shirmiley.quizon@bugardi.com.au', 'shirmiley.quizon@gmail.com', '123456', 'Shirmiley Canlas', 'Quizon', 'Bugardi', 'Recruitment Mobilization Officer', '210-283-638-000', 6, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:22'),
(109, '1003', 'tina.pangan', 'Tina.Pangan@resourcestaff.com.ph', 'thine2miranda@gmail.com', '123456', 'Cristina Miranda', 'Pangan', 'RSS', 'Executive Assistant to the General Manager', '0915 056 1780', 4, 'internal', 'hr', 'active', NULL, 'dtk57MRQO6tTSk838rvvaR:APA91bELQjphdUEfNwwr5SjxVmVNcyyfMgGj17d4W57lfGlcYTcpGzWlSzEVU8b2Tb7mgIGr7CPeiIgmP7gA03zIZ9aMYYFeW0UWwbjs923xj91c6DDza9c', NULL, '2026-02-11 06:50:21'),
(110, '1050', 'Trisha.Elias', 'trisha.elias@entrygroup.com.au', NULL, 'Tanginamo123!', 'Trisha', 'Elias', 'Entry Group', 'Conveyancing Client Officer', '09950338687', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:20'),
(111, '69', 'trisha.mcgregor', 'trisha_mcgregor@yahoo.com', 'trisha_mcgregor@yahoo.com', '123456', 'Trisha Mae Adriano', 'McGregor', 'Ridge Renovation', 'Renovation Draftsman', '09289852739', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:21'),
(112, '88', 'vincent.cabico', '', NULL, '123456', 'Vincent', 'Cabico', 'Venaso', 'Draftsman', '09995582957', 16, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(113, '1', 'vincent.santos', 'Vincent.Antonio@entrygroup.com.au', 'vincentvinz17@gmail.com', '121819', 'Vincent Kevin', 'Santos', 'Entry Education', 'Student Support - Marking', '0905 919 2943', 4, 'employee', NULL, 'active', 'profile_1_1750742776.png', NULL, NULL, '2026-02-11 06:50:22'),
(114, '34', 'yris.camerino', 'yyrish@gmail.com', 'yyrish@gmail.com', 'qwe987*', 'Yris Gaelle', ' Camerino', 'Entry Education', 'Student Support - Marking', '0912 937 9482', 13, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:17'),
(115, '71', 'zahra.cabusao', 'zahracortez95@gmail.com', 'zahracortez95@gmail.com', '051995', 'Precious Zahra Cortez', 'Cabusao', 'Leeway Group', 'Hydraulics Estimator', '326-526-766-000', 4, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-11 06:50:18'),
(1058, NULL, 'Justine.Jabat', NULL, NULL, 'trswa_justine', 'Justine', 'Pascua', NULL, 'Operations Administrator', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-13 08:08:31'),
(1059, NULL, 'Daniel.Mira', 'daniel.mira@entrygroup.com.au', NULL, 'entry_daniel', 'Rowen Daniel', 'Mira', 'Entry Education', 'Sales Support', '09294648226', NULL, 'employee', NULL, 'active', 'profile_1059_1771210616.png', NULL, NULL, '2026-02-13 08:18:40'),
(1060, NULL, 'Lance.Fernandez', NULL, NULL, 'rss_lance', 'Lance Jio', 'Fernandez', NULL, 'Digital Marketing', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-14 06:48:18'),
(1061, NULL, 'Nerine.Calabia', NULL, NULL, 'entry_nerine', 'Nerine', 'Calabia', NULL, 'Technical Student Support', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-21 08:39:51'),
(1062, NULL, 'Trixie.Linga', 'trixiejhemlinga@gmail.com', NULL, 'entry_trixie', 'Trixie Jhem', 'Linga', 'Resourcestaff', 'Technical Student Support', '09928902147', NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-02-21 08:42:06'),
(1063, NULL, 'Jessica.Uncad', NULL, NULL, 'entry_jessica', 'Jessica', 'Uncad', NULL, 'Bookkeeper', NULL, NULL, 'employee', NULL, 'active', NULL, NULL, NULL, '2026-03-01 22:58:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
