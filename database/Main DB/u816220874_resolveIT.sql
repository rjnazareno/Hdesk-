-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 06, 2026 at 02:52 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u816220874_resolveIT`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `color`, `is_active`, `created_at`) VALUES
(1, 'Hardware', 'Hardware related issues (computers, printers, phones)', 'desktop', '#3B82F6', 1, '2025-10-08 03:37:59'),
(2, 'Software', 'Software installation and troubleshooting', 'code', '#10B981', 1, '2025-10-08 03:37:59'),
(3, 'Network', 'Network connectivity and access issues', 'wifi', '#F59E0B', 1, '2025-10-08 03:37:59'),
(4, 'Email', 'Email account and configuration issues', 'mail', '#EF4444', 1, '2025-10-08 03:37:59'),
(5, 'Access', 'System access and permission requests', 'key', '#8B5CF6', 1, '2025-10-08 03:37:59'),
(6, 'Other', 'Other IT support requests', 'help-circle', '#6B7280', 1, '2025-10-08 03:37:59'),
(7, 'Harley', NULL, 'fa-bug', '#6b7280', 1, '2025-10-13 01:38:28'),
(8, 'HR', 'this category is for HR related only', 'fa-envelope', '#ef4444', 1, '2025-11-14 01:35:41');

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
  `role` enum('employee','manager','supervisor') DEFAULT 'employee',
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `username`, `email`, `personal_email`, `password`, `fname`, `lname`, `company`, `position`, `contact`, `official_sched`, `role`, `status`, `profile_picture`, `fcm_token`, `profile_image`, `created_at`) VALUES
(1, '1', 'vincent.santos', 'Vincent.Antonio@entrygroup.com.au', NULL, '$2y$10$N0waCj/5zu6MKllawHUlTOc9K5evXFsLsyuv4V0TXBjk6/cncSGUq', 'Vincent Kevin', 'Santos', 'Company Inc', 'Student Support - Marking', '1234567890', NULL, 'employee', 'active', NULL, NULL, NULL, '2025-10-08 03:37:59'),
(2, 'TEST001', 'alice.johnson', 'alice.johnson@company.com', NULL, '$2y$10$cY33BqpnweebC3RzzqT6H.rhhy8RQnRg1BYc1M6GyRhqtZ/xKe.mi', 'Alice', 'Johnson', 'Marketing', 'Marketing Manager', '555-0101', NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 01:30:28'),
(3, 'TEST002', 'bob.smith', 'bob.smith@company.com', NULL, '$2y$10$IDEFEi6nrzrC4eqMggs5yOQ2hxY/XqXsjboGmHa6lBhdI.tvAVxca', 'Bob', 'Smith', 'Sales', 'Sales Representative', '555-0102', NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 01:30:28'),
(4, 'TEST003', 'carol.williams', 'carol.williams@company.com', NULL, '$2y$10$wuJijWbdHuJOVi8CgeKwBuuEzAX1q898jnpKEbm3dBPN/GgWOgIgi', 'Carol', 'Williams', 'IT', 'Software Developer', '555-0103', NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 01:30:28'),
(5, '2', 'shaina.dela cruz', 'shainadc86@gmail.com', NULL, '$2y$10$D5FvIwexm58MPgmiwiygjOqKu5BtxV2poKj.QY09jak7JufM55d22', 'Shaina', 'Dela Cruz', NULL, 'Student Support Mentoring', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:08'),
(6, '3', 'renalyn.josafat', 'reny@entrygroup.com.au', NULL, '$2y$10$7PWFDjZ2A9DriJcy6Z5Q7.1Uej8uH0LVBd/106y7GEVO5isrpYzZu', 'Renalyn Abamo', 'Josafat', NULL, 'Student Support Mentoring', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:08'),
(7, '4', 'joel.alimurong', 'Joel.Alimurong@entrygroup.com.au', NULL, '$2y$10$YVjOugF3.56tIB0KS6njl.Y6uT7.VAdH..zhuPh65xSwwVXDooKDu', 'Joel Lusung', 'Alimurong', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:08'),
(8, '5', 'aizel.castro', 'aizel.castro@entrygroup.com.au', NULL, '$2y$10$nIJHuqz3zx7M3BEYscBZzuOykLfOXFzsiAcZTg3U57eare2IO4kfm', 'Aizel Santos', 'Castro', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:08'),
(9, '6', 'reymark.colis', 'bryan@entrygroup.com.au', NULL, '$2y$10$PPV1pjZGoZ35gE5k.OywuOFC.68y17yRs/wUsQeytFX5NyaMi1MpS', 'Reymark Bryan Silvano', 'Colis', NULL, 'Technical Student Support - Team Leader', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:08'),
(10, '7', 'francis.fernandez', 'francis@entrygroup.com.au', NULL, '$2y$10$VIDUGlHkzCGsVkh5pwk3GObkBnrn.j0UABGOcbUrQDP59GYpX8c3O', 'Francis Emmanuel Veloso', 'Fernandez', NULL, 'Student Support - Marking Team Leader', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(11, '8', 'cedrick.galgo', 'Cedrick.Galgo@entrygroup.com.au', NULL, '$2y$10$sFowZrf0vJ8qU/tValkeAeQ0NxB3cEPIGsRqDASpBmDIKFNw9nZfu', 'Cedrick', 'Galgo', NULL, 'Student Support - Marking (Part Time)', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(12, '9', 'shigeru.centina', 'Shigeru.Otsuka@entrygroup.com.au', NULL, '$2y$10$lV4sc1p5588B1hh1eW9ZLem/Y4fSRU.w8kaQ0EgKZWBzTJ..4wIDa', 'Shigeru', 'Otsuka', NULL, 'Instructional Designer - Technical Specialist', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(13, '10', 'rhegene.ronquillo', 'reggie@entrygroup.com.au', NULL, '$2y$10$Fs5LWVmQox7mqbj8iRQFq.khjaXq7KYaPGlocEfnunWUdbgOu9BHu', 'Rhegene', 'Ingat Ronquillo', NULL, 'Technical Student Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(14, '11', 'mary.soriano', 'mary@entrygroup.com.au', NULL, '$2y$10$qkfn9jn7HfizTK/RxuPngOY/JeTj99AaniJVWroxvDMaz8fUx3gLW', 'Mary Ann', 'Vallejos Soriano', NULL, 'Sales New Student Enquiries', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(15, '12', 'beverly.gatbonton', 'beverly.gatbonton@entrygroup.com.au', NULL, '$2y$10$8OfNsELUjRgFX7wK14GtDu7hyIzBIPlMJ18yOX0RaVqDmNiEfW/ua', 'Beverly', 'Taloban Gatbonton', NULL, 'Team Leader Sales - New Student Enquiries', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(16, '13', 'rogelio.malinao', 'rogelio.malinao@entrygroup.com.au', NULL, '$2y$10$UW8pNwOlXAEd8AWUYKSxpuuCT.j68BJi4dkzTJdnuVyMVs3U6vHly', 'Rogelio', 'Dela Peña Malinao Jr', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(17, '14', 'Jillian.Agas', 'jillian.agas@entrygroup.com.au', NULL, '$2y$10$Hlh9BDhXxb97MWGF7wfQ6umBTfkPDCsbHfqhqSh8nIozxTmDdSuSO', 'Jillian', 'Agas', NULL, 'Technical Student Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(18, '15', 'evanel.navalon', 'evanel.navalon@entrygroup.com.au', NULL, '$2y$10$4SsAnt3sCESNHqi9NcsrTOXg56CCibJ5bysXsg77pP3vPEE30uRFe', 'Evanel', 'Caacbay Navalon', NULL, 'Technical Student Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(19, '16', 'ian.aguilar', 'ian.aguilar@entrygroup.com.au', NULL, '$2y$10$ZZrPaSTGUfoRbAwAXiAsIugIKNwc4tR.cRPSLKxkqmR8NzZ7ou5ha', 'Ian Myco', 'Aguilar', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(20, '17', 'reneeca.benalla', 'Reneeca@entrygroup.com.au', NULL, '$2y$10$HEyCiTlzxAgdL1tOS8qcFOJw8Uq3/LgLqFn4cwR1S1skOlqQ2WYfu', 'Reneeca', 'Villapaña Benalla', NULL, 'Content Writer &amp; Instructional Designer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(21, '18', 'edith.mataga', 'Edith@entrygroup.com.au', NULL, '$2y$10$3Av5/V5fJGWafkyXWa4HWuFi8D0UxOE5Dr6Y8MTdabhB3CFeNt5Xe', 'Edith', 'David Mataga', NULL, 'Sales New Student Enquiries', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(22, '20', 'alfred.ocampo', 'Alfred@entrygroup.com.au', NULL, '$2y$10$gWjOVK.Nz76QwZ4dABnYD.QnGXw6WiTAMObrhBQJVEs9AbzIhkwDK', 'Alfred Naguit', 'Ocampo', NULL, 'Conveyancing Client Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(23, '21', 'jennifer.trinidad', 'jennifer.trinidad@entrygroup.com.au', NULL, '$2y$10$wh7LVSj5mt9/J9esHhkLHO1sSHYYrRyB3begBF2BH1lZ4e9iykKLi', 'Jennifer', 'Trinidad', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(24, '22', 'sean.mendoza', 'sean.mendoza@entrygroup.com.au', NULL, '$2y$10$5iXRhsDI.cO48FDrtdawIugA0sxYX0a9h.EvZOe8ckcXQgzegIsDG', 'Sean Justine', 'Mendoza', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(25, '24', 'elritz.crisanto', 'elritz.crisanto@entrygroup.com.au', NULL, '$2y$10$4ncig7LVuHJA.oYvFA1nrOFi4ZTTXQ5uvAssSMQ8uz0SjXTXgIK32', 'Elritz', 'Crisanto', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(26, '25', 'analiza.gatbonton', 'analiza.gatbonton@entrygroup.com.au', NULL, '$2y$10$lGj.fS5TILTqrtdVd6fnFOsHOLYTYgg8uLjkLX7tNjsD24WCcciq2', 'Analiza', 'Taloban Gatbonton', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(27, '26', 'franklin.pabillano', 'estimating@empirewestelectrical.com.au', NULL, '$2y$10$hByha5RdQa10K0XLedoe8uw9qPKGkfY3QhDb1ileq/kly0PgIWA7O', 'Franklin Roos', 'Cinco Pabillano', NULL, 'Electrical Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(28, '27', 'kristian.bansil', 'ITsupport@mtunderground.com', NULL, '$2y$10$HH3fs8dbiVC3iALP8gIzW.MtChu5ewU7d/oqC8IKd7a742pDL7SvO', 'Kristian David', 'Bansil', NULL, 'Web Developer / Admin &amp; IT Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(29, '28', 'louis.austria', 'louis.austria@entrygroup.com.au', NULL, '$2y$10$DMBcekv1SGWw0asrZ1jWBuaMkIv273tX5IbjnmCF.vStgjqiRAIH.', 'Louis Fernand', 'Austria', NULL, 'Graphic Designer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:09'),
(30, '29', 'johana.gueco', 'johanarose.gueco@entrygroup.com.au', NULL, '$2y$10$atYy07WpCTSD.8ZehHK2ZeckXV.o/1VlYdvWJCllcdjXrbrGI.hs6', 'Johana Rose', 'Perez Gueco', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(31, '30', 'erika.pineda', 'erika.seriosa@entrygroup.com.au', NULL, '$2y$10$7nWrxpetG6BRUiIs5KuG6eq3G4y2FTi78JUwS2EcFsbB2u/Tgj1si', 'Erika', 'Seriosa Pineda', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(32, '31', 'jhunel.samodio', 'jhunelcarlo.samodio@entrygroup.com.au', NULL, '$2y$10$kuQ36tR7bkYsZI8EuD3HMelprOIIqCL3r3kaMA.4RZ6bk7PPT0yRa', 'Jhunel Carlo', 'Traifalgar Samodio', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(33, '33', 'aldwin.lozano', 'aldwinjohn.lozano@entrygroup.com.au', NULL, '$2y$10$YdqskCqExcyz5kEyoS8EnOtHnVh2bqHplETYSH7HTteyMvwc0QEWe', 'Aldwin John', 'Arceo Lozano', NULL, 'Sales New Student Enquiries', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(34, '34', 'yris.camerino', 'yyrish@gmail.com', NULL, '$2y$10$BDQD8fPMYJLYqb3dAizqL.v982eXmM8L2q7WsUnpAQ.oPy9Ut4IIG', 'Yris Gaelle', 'Parreñas Camerino', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(35, '35', 'nika.bacongallo', 'nika.bacongallo@gmail.com', NULL, '$2y$10$M0xZVwLbjodPIgoA08SlrOHW//k.IP1lPAZSBS66UMTKDFdJmMQHG', 'Nika', 'Nueva Bacongallo', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(36, '36', 'denver.castillano', 'dcstudio.creative@gmail.com', NULL, '$2y$10$mOP11e2NTVHJpJY5bIYsBezRphifVUmA6zST8SFN7eNxQD7iOA.9q', 'Denver Orlanda', 'Castillano', NULL, 'Draftsman', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(37, '37', 'marnie.catalogo', 'marniecatalogo99@gmail.com', NULL, '$2y$10$GNDvytV1DTr6qzLYtN7khOZlUocoBXNoxx5jja6gd1GScrU4VunNm', 'Marnie', 'Perez Catalogo', NULL, 'Technical Student Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(38, '38', 'ryan.patrimonio', 'rexryanpatrimonio@gmail.com', NULL, '$2y$10$XSbirvA6QjD9kzm87RpZDuxvE/LtvI2nRUw5R68xP85mV.WEB/kKe', 'Ryan Rex', 'Patrimonio', NULL, 'Technical Student Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(39, '41', 'charisma.platero', 'charisma.platero@gmail.com', NULL, '$2y$10$iPPjDZKQYniqtwOnXj/LueAUxiVNqaPHITq1fukyzlb1Np8RDEFE.', 'Ma. Charisma S.', 'Platero', NULL, 'Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(40, '43', 'lovelaine.celeste', 'lovelaineceleste@yahoo.com', NULL, '$2y$10$z.O8uIMkIVqfUIBdbv7hAew/np/cYq0Av9lT52LrNGMyMvKwJhdaq', 'Lovelaine', 'Celeste', NULL, 'Sales New Student Enquiries', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(41, '46', 'ivy.nuñez', 'ivynunez26@gmail.com', NULL, '$2y$10$zaDV3lujnMpbUihhb6aHPuTUFiDWLod3CjFFxwRlpfsmyceYi4WoK', 'Ivy', 'Nuñez', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(42, '47', 'glory.balderas', 'Glory@fratellihomeswa.com.au', NULL, '$2y$10$QFd.PZhLLiqxcEmiZJcTXu.8hctYCVMKVA38mPLwLO2imPAwsp9lq', 'Glory Ann', 'Garcia Balderas', NULL, 'Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(43, '49', 'julie.maclang', 'macjulg08@gmail.com', NULL, '$2y$10$mYN/5E1DzCUDkMPvd2QdKOR9f8OuNcd.v0DjIUZEwjzQHsaKIs3Iu', 'Julie Anne', 'Guinto Maclang', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(44, '50', 'francis.bondoc', 'francis.bondoc22@gmail.com', NULL, '$2y$10$78/lItlaR6P21aaGOLe9ieRnSFncs5/A3LDDNPVkF3DuoLpOPx1ra', 'Francis Eugene Aguhayon', 'Bondoc', NULL, 'Draftsman', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(45, '52', 'althea.makabenta', 'document.control@bugardi.com.au', NULL, '$2y$10$IX98jdWkKO9Y.2b44HQ3p.Of9vzWVeu0EMBTbICe9s0TOa5DkHGfK', 'Althea Tansingco', 'Makabenta', NULL, 'Document Controller', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(46, '53', 'christian.mar', 'christianmar673@gmail.com', NULL, '$2y$10$U6a9b92B7hnobf9GgUCJzeKcBYQ/MYhrCZ5/m220QLZGv.UhOSJxG', 'Christian Nioda', 'Mar', NULL, 'Sales New Student Enquiries', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(47, '55', 'jeffry.macapagal', 'jeff.macapagal017@gmail.com', NULL, '$2y$10$XoR37viAMW.EifuIMYJYfeDvonc5BpyZMZ.OLvnpehtun/i4/yUQG', 'Jeffry Tuazon', 'Macapagal', NULL, 'Operations Administrator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(48, '56', 'allen.capati', 'allen.capati@entrygroup.com.au', NULL, '$2y$10$oXYm05k.YHVydnDqtD8zs.0bF5Co1pF.ys8QpdbY8BB7XwcH7htEC', 'Allen Sobrepeña', 'Capati', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(49, '57', 'angelica.estanio', 'angelica.estanio@entrygroup.com.au', NULL, '$2y$10$j5Tv1p1a9NNMXcw3WPM2mee.6dEz4uLTWuC50N3S9u/UhFDlbokaW', 'Angelica Rosario', 'Estanio', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:10'),
(50, '58', 'adonis.jabinal', 'adonis.jabinal@bugardi.com.au', NULL, '$2y$10$vO/bmosAtElUYGrpVNs3NOzyllK0TuPe0Ddc044LUNArdI49rS5wi', 'Adonis Del Mundo', 'Jabinal', NULL, 'Project Coordinator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(51, '59', 'joshwea.monis', 'joshwea.monis@entrygroup.com.au', NULL, '$2y$10$JzBkXjw0VCzn2KkQPrFQ/uHg2ysValfkSMCQMn1V/XlT9fwGiCe9e', 'Joshwea Mercado', 'Monis', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(52, '60', 'janeth.solayao', 'janeth.solayao@entrygroup.com.au', NULL, '$2y$10$/Q62q0tOzfFmMlcwt5mvpuaCKeKfHjdwCNreNCNbEBVVjOphGqOFS', 'Janeth Sedon', 'Solayao', NULL, 'Student Support - Marking', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(53, '61', 'rj.singh', 'rvschk@gmail.com', NULL, '$2y$10$TzldukZmWzG01AZQuq2Pau/1q/aqbzcw6UCW5mwxSp6fVPcOuc9mG', 'Ray Jinder Villena', 'Singh', NULL, 'Executive Assistant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(54, '62', 'shirmiley.quizon', 'shirmiley.quizon@bugardi.com.au', NULL, '$2y$10$qaS0uy0azy.Ew9lIDFFn7ODpkgryZXAs4CjnogAUKEtOG9XJTqF0S', 'Shirmiley Canlas', 'Quizon', NULL, 'Recruitment Mobilization Officer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(55, '63', 'Nina.dollentes', 'nina.dollentes@entrygroup.com.au', NULL, '$2y$10$Ax/Fa4OOUUrnq2zjeIu3HumPUc4sXjNbnTGmGAFQlJHUKVVJSp0S6', 'Maria Ñina', 'Dollentes', NULL, 'Accountant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(56, '64', 'jerzi.libatique', 'jerzi.libatique@entrygroup.com.au', NULL, '$2y$10$evkh1J79mvRpL99u0M698.6VOlBOtNYwmXsyKT5V.LWq12OabDfza', 'Jerzi Chezka Medel', 'Libatique', NULL, 'Accountant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(57, '65', 'Lester.nuñeza', 'lester.nuneza@bugardi.com.au', NULL, '$2y$10$P0oIkfoNN4SIO/6upmvL5eknmKNgQCG9L.9d0fGk9uQbemtLlt4a2', 'Sabando', 'Nuñeza Dou Lester', NULL, 'HSEQ Assistant Manager', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(58, '66', 'godwin.ocampo', 'tgodbtg04@gmail.com', NULL, '$2y$10$Eczr2SO24uTMQUDr5oCUuO1eyo9lfxCv/wuxqH9goL4xrVe.z4PKC', 'Godwin', 'Ocampo', NULL, 'Tax Accountant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(59, '67', 'apryl.pasion', 'apryl.pasion@bugardi.com.au', NULL, '$2y$10$xmBgC5f92pmdj36lU9cLMux55Ss4Xx2EAs.HrgMBqtl4mb2i0MHuW', 'Apryl Ordonio', 'Pasion', NULL, 'Recruitment Mobilization Officer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(60, '68', 'christine.angeles', 'christinekhlaryss@gmail.com', NULL, '$2y$10$hIBw7DtrWN9q.oHxMDtkqOmA9THni0ToaSF0l2kv2H9k8ZBmGkdDS', 'Christine Khlaryss', 'Angeles', NULL, 'Tax Accountant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(61, '69', 'trisha.mcgregor', 'trisha_mcgregor@yahoo.com', NULL, '$2y$10$kCLqZW7lJOEvzf/imM6H..QkSC.NxnWbGJEPVtud0OdXSvAhtqJIG', 'Trisha Mae Adriano', 'McGregor', NULL, 'Renovation Draftsman', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(62, '70', 'jm.briones', 'jamenabriones14@gmail.com', NULL, '$2y$10$staiLwPuUR4EfdsoUu9Fd.Yy2lbRRkKzsb9BPuskWT/vLr.zuviNO', 'John Michael Comprado', 'Briones', NULL, 'Commercial Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(63, '71', 'zahra.cabusao', 'zahracortez95@gmail.com', NULL, '$2y$10$qY5H.oaK/wUVZyZhK97S2e6uWQUIw6FX1PPBv9sqWHzcDdxzuw9jG', 'Precious Zahra Cortez', 'Cabusao', NULL, 'Hydraulics Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(64, '72', 'Milbert.Sambile', 'milbert@millersroofing.com.au', NULL, '$2y$10$8mPkWuFLmBAU3e.07CwG7e.nRY6Lxyg7xBoIKvggvJCpAVOGsPW9.', 'Milbert', 'Sambile', NULL, 'Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(65, '73', 'Ryan.David', 'davidryarwin@gmail.com', NULL, '$2y$10$/PrZ6nAjSRp8EmQPLNSFveTQGUdJAlxgz4kV2uM4NGowAgTEL5oCK', 'Ryan Arwin', 'David', NULL, 'Operations Admin', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(66, '74', 'John.Alvarez', 'johnalvarez930@gmail.com', NULL, '$2y$10$tbnucJRvwL4gZhWukgsGwOfhd1iX4t9ZwFX4tPNhpnOUW10d6hRhK', 'John Bryan', 'Alvarez', NULL, 'Operations Admin', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(67, '75', 'Oliva.Bautista', 'olivesantos.bautista@gmail.com', NULL, '$2y$10$LQfpIC4U.RpJ4l7BZtkxROP.uSAa35EoHHWNYXYf4HcJsumLxNeAO', 'Oliva', 'Bautista', NULL, 'Operations Admin', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(68, '76', 'Sarah.Caraan', 'sarahcaraan31@gmail.com', NULL, '$2y$10$Qw7JCN8.mglLNH6wUOmAeuD1Vwo5B7/HsdOXQochPk4L.HozPfpCy', 'Sarah', 'Caraan', NULL, 'Operations Admin', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(69, '77', 'Alfie.Guillermo', 'alfie.guillermo0219@gmail.com', NULL, '$2y$10$sAdDDrzlHrkBHxwXZfsvD.rf1zwYexEgGVz4iJl/C4a4iSBGBIvvO', 'Alfie', 'Guillermo', NULL, 'Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:11'),
(70, '78', 'Brittany.Yulo', 'yulobrittany@gmail.com', NULL, '$2y$10$L5nvqjapWLFCILMjv16W4u2GLXS9O8FPvTGGSE8Mp./jjfobSRN62', 'Brittany', 'Yulo', NULL, 'Commercial Assistant Administrator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(71, '79', 'John.Sanoza', 'sanozajohn@gmail.com', NULL, '$2y$10$T1j78x0XmMPJXXTGpYmYi.RkYpKzr1dgYYCfDqU3GAuskSwTkeu7i', 'John', 'Sanoza', NULL, 'Senior Full Stack Developer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(72, '80', 'Jae.Fernandez', 'mjae.fernandez@yahoo.com', NULL, '$2y$10$oLu4Y9zV8Lrqfv5gAzuy0.vEsxleJveybwGwvg6tMDU5Gpo9MjjXm', 'Marianne Jae', 'Fernandez', NULL, 'Administrative Assistant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(73, '81', 'Sherry.Patawaran', 'marketing@hammerhire.com.au', NULL, '$2y$10$046S/BMDTJyeS3AjBoTccelN0GlXLK0DVwo9E2CQIYgzE.yXPcLLG', 'Sherry Rose Ann', 'Patawaran', NULL, 'Marketing Coordinator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(74, '82', 'Gabriel.Capiral', 'capiralgabriel@gmail.com', NULL, '$2y$10$XqsyFx0M40e/tfxmyGIM6Oz3xh0agcrav5.UX3kkcZqUEXIxH.PM.', 'Gabriel', 'Capiral', NULL, '', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(75, '87', 'lance.libo', 'lancejomerlibo@gmail.com', NULL, '$2y$10$KtFAZE3RREgtkmMipAEk/.HlqybwdCF3VqGcqVh7hoKZJGt2T0cRy', 'Lance', 'Libo', NULL, 'Front-end Flutter Developer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(76, '89', 'roxanne.tulaytay', 'roxanne.tulaytay@gmail.com', NULL, '$2y$10$WKh8/Cp/L9gnwl.ENNYFauBEJsKmq/LgBTf8BezN9OYj2hR1iCBim', 'Roxanne', 'Tulaytay', NULL, 'Accounts Payable Officer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(77, '1002', 'neil.costelloe', 'Neil.Costelloe@resourcestaff.com.ph', NULL, '$2y$10$apxGyRcYI68jut1szL2nVuh9EsnqHfA8VBpawgNDNBzNrQ5yJqCSe', 'Neil Anthony', 'Costelloe', NULL, 'General Manager', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(78, '1003', 'tina.pangan', 'Tina.Pangan@resourcestaff.com.ph', NULL, '$2y$10$T70jkdqsHvLnSG8pZnlXj.9f3B0JynzpuYOchnVgSVzwDx0JZ24QC', 'Cristina Miranda', 'Pangan', NULL, 'Executive Assistant to the General Manager', NULL, NULL, 'employee', 'active', NULL, 'cO8TCfjSpK33jXYHvT23lX:APA91bEjylozLheMlJBLi49DX_D0kGwgadmFlksMI5_YISpkstArylwQKAhHlg0wn79R2RDRmqm8_WjgVN9BE59rx3ZQiamyWB8Ji5PSoETfW0slv4z44L8', NULL, '2025-11-05 06:49:12'),
(79, '1004', 'rj.tolomia', 'Rica.Tolomia@resourcestaff.com.ph', NULL, '$2y$10$7nVKKsS7.wAedHjD5VJxOOUHBtCho01OzDQR9O7twsGMRwhFwlqmG', 'Rica Joy Viray', 'Tolomia', NULL, 'TA/HR Specialist', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(80, '1005', 'johsua.dimla', 'johsua.dimla1986@gmail.com', NULL, '$2y$10$ycyD.dOymGav3flvtFxuleVMmR47EbAe.ieXs84/JN5CaWXJS/DMW', 'Johsua Torninos', 'Dimla', NULL, 'Facilities and Admin Support', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(81, '1006', 'Cedrick.Arnigo', 'Cedrick.Arnigo@resourcestaff.com.ph', NULL, '$2y$10$2Tq3bHryHK8gI/MWjmN3B.j3FPl5GwHKWcDSi6J0M/yFVK/X.riaa', 'Cedrick', 'Arnigo', NULL, 'IT Support Specialist', NULL, NULL, 'employee', 'active', NULL, 'cO8TCfjSpK33jXYHvT23lX:APA91bEjylozLheMlJBLi49DX_D0kGwgadmFlksMI5_YISpkstArylwQKAhHlg0wn79R2RDRmqm8_WjgVN9BE59rx3ZQiamyWB8Ji5PSoETfW0slv4z44L8', NULL, '2025-11-05 06:49:12'),
(82, '1007', 'Peach.Herrera', 'herrerafelicci@gmail.com', NULL, '$2y$10$WO4oXRTCiXUqPPRD2h7rW.QwotHp9wmQx5cS1kna/l9uTnzUFJnD2', 'Peach', 'Herrera', NULL, 'Admin', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(83, '1009', 'Kiras001', 'rjmanago@gmail.com', NULL, '$2y$10$vDb7QE7peXiDJ0yvo5LZheuX5AS5c1s8QbMiKE8QeSVgDhJePpBfi', 'Resty', 'Nazareno', NULL, 'IT Intern', NULL, NULL, 'employee', 'active', 'profile_691bd194c9cda.jpg', 'cO8TCfjSpK33jXYHvT23lX:APA91bHz4miYlECcQEY3S2uUvUleWeOfAHbs5--ueef7NZDB33Fxdd_-vT-xoKe-jUHyJPHDgntFCOe-4VbDw8GapPjC5inthNYsBNcgYGaV39lv-9u0lUo', NULL, '2025-11-05 06:49:12'),
(84, '1024', 'alex.tayao', 'alextayao23@gmail.com', NULL, '$2y$10$iINK90.U.uR9o19VcdEjHOwc21EXvXoihIXEPHCAeOESETCwp/MYK', 'Alexander', 'Tayao', NULL, 'Draftsman', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(85, '1027', 'rebecca.david', 'rebeccad.david@gmail.com', NULL, '$2y$10$erwmM3PN3ro.vja3Vghv..y8zZGrxLl3Amwt.9V/MmXVJ.xGY8K1O', 'Rebecca', 'David', NULL, 'Bookkeeper', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(86, '1030', 'Ron.Cueto', 'ron.cueto@bugardi.com.au', NULL, '$2y$10$We6na6x.o5lojz8bBm2Lf.1QIsvpgsHwTRcIo2slg02t24ENBnp9C', 'Ron', 'Cueto', NULL, 'Design Coordinator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(87, '1033', 'Mylene.Torres', 'mylene.dauz@yahoo.com', NULL, '$2y$10$Dp1ok1/c/S6zYqYOD6BnYOnd9aD0dD.wJsAWZbw9doYeBCTtcnBGG', 'Mylene', 'Torres', NULL, 'Accounts Payable Officer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:49:12'),
(88, '83', 'Joshua.Manalili', '83@noemail.local', NULL, '$2y$10$LBE/ZYJ5sWmgJn5BFp/Gm.15orvdQ.EsrD62PyiGws1Pww7rUDUuO', 'Joshua', 'Manalili', NULL, 'Network Controller', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:33'),
(89, '84', 'Roi.Pangilinan', '84@noemail.local', NULL, '$2y$10$JyAbUSKPQ2vXYtKjn239iOmHWVLFtGvnHi66jxH8tdbmwrhdQ4702', 'Roi Dane', 'Pangilinan', NULL, 'Network Controller', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:33'),
(90, '85', 'Paul.Pasion', '85@noemail.local', NULL, '$2y$10$Wx0nodE71o4IzWuWNbu27uiiWwEAI/Pk9mu0Zi32fOE9S2yURvJ0a', 'Paul', 'Pasion', NULL, 'Network Controller', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:33'),
(91, '86', 'Jonas.Delacruz', '86@noemail.local', NULL, '$2y$10$xabyZR5wdR3F6GmYEvn.2eDA81KYK5HImk6UHQ7ig.y0pLgDVzUNu', 'Jonas', 'Dela Cruz', NULL, 'Draftsman', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:33'),
(92, '88', 'vincent.cabico', 'michealvincicabico@gmail.com', NULL, '$2y$10$6QR2WHNbWiSiJIA8NyrgheqKlJKfs4ABUtwqrHCoLKzOAz5LhDekK', 'Vincent', 'Cabico', NULL, 'Draftsman', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:33'),
(93, '1025', 'kim.dacquil', 'kimberlydacquil.1225@gmail.com', NULL, '$2y$10$4./NiROeg5.6zW7nsNgEHOX7IQsihyoy6dmz6mwzf0jRe9f6TVtg2', 'Kimberly', 'Dacquil', NULL, 'Accounts Administrator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:33'),
(94, '1026', 'karen.belangel', 'karenbelangel@gmail.com', NULL, '$2y$10$P3W8ZokafHh3y4bN9Txm8u0EhGQ9a/j0as.awZN6iG8LMlCmqUVOG', 'Karen', 'Belangel', NULL, 'Executive Assistant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:34'),
(95, '1028', 'Joseph.David', 'josephdavid521@gmail.com', NULL, '$2y$10$J3GwOeRIL6rP/7INktvYdu7Kew8BN4H7iEIlH4e4DT/K0vaDZ192.', 'Joseph', 'David', NULL, 'Electrical Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:34'),
(96, '1029', 'Chloedean.Flores', 'deaaan920@gmail.com', NULL, '$2y$10$kqhJnuzDkFso73bPbbQ5gOqP4zD1DOE0oZI6DKLcIYhEwhV1Psr.S', 'Chloedean', 'Flores', NULL, 'Genereal Service Administrator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:34'),
(97, '1031', 'Kryssa.Gabatino', 'kryssagabatino17@gmail.com', NULL, '$2y$10$4H9pOMq4eOCty2h8zj.o.uUSZX4dzMygRqNcB6TmvsLphrBUeKBia', 'Kryssa', 'Gabatino', NULL, 'Administrative Assistant', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:34'),
(98, '1032', 'Carl.Tupaz', 'tupaz.carldave@gmail.com', NULL, '$2y$10$h0oKQK9IcITV1l44lvu6B.2rCbtz5U01CG.ByuUzOKkMbGsmJSwtK', 'Carl', 'Tupaz', NULL, 'Electrical Estimator', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-05 06:50:34'),
(99, '1034', 'Jairus.Ignacio', 'jairusignaciogomez@gmail.com', NULL, '$2y$10$w0qjOcpduJPVsA4H.LAaEu75dZVxAz5uycMRFKkHl/SRC4fyv9xw2', 'Jairus', 'Ignacio', NULL, 'Digital Marketing', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(100, '1035', 'RJ.Tiglao', 'jaeliz0529@gmail.com', NULL, '$2y$10$BmTnO7sTgQkXy9S6UfLbq.5xKpFEIcxUcIouEZ9a0fgop2p4ixl/q', 'Ryan Jae', 'Tiglao', NULL, 'IT/Data Analyst', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(101, '1036', 'Nash.Guevarra', 'Jeiel.Guevarra@stoddarts.com.au', NULL, '$2y$10$ddhaN.rf72IRsSjjyHcu/OKSG7q3Vf36xwZ89eh5FbAhrBWNXZ7.W', 'Jeiel Nash', 'Guevarra', NULL, 'Architectural Detailer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(102, '1037', 'Elmer.Gagote', 'Elmer.Gagote@stoddarts.com.au', NULL, '$2y$10$miNGSVo4F9vnjR.mFazl8O5hfh3xhI6ijMuV3YRbab70Y/nHM8Gna', 'Elmer', 'Gagote', NULL, 'Architectural Detailer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(103, '1038', 'Jay.Asuncion', 'Jay.Asuncion@stoddarts.com.au', NULL, '$2y$10$DIl4mmnGQ3nGrKV1gbnaXO3JJwFbAnx/UYejenWIQOnDzJ0BFtBZa', 'Jay Kimbert', 'Asuncion', NULL, 'Architectural Detailer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(104, '1039', 'Abi.Bunda', 'Abigael.Bunda@stoddarts.com.au', NULL, '$2y$10$mgI854LQZa0MF22Dh59/sOgC7uVL1RvzbP646wLdKyJalr6bzdgLe', 'Abigael', 'Bunda', NULL, 'Architectural Detailer', NULL, NULL, 'employee', 'terminated', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(105, '1040', 'Eri.Otaka', 'Eri.Otaka@stoddarts.com.au', NULL, '$2y$10$tUzx/WOmXsYAEsu6RPBzf.gEvmocmSbB.7mtBeRr7L61PgKZF1/uO', 'Eri', 'Otaka', NULL, 'Architectural Detailer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(106, '1041', 'Cesar.Cochon', 'Cesar.Cochon@stoddarts.com.au', NULL, '$2y$10$bJyUlIQG9XhPIMlqx2k6ZOGSd4QNtE7VMBWgEIKW36941NjT7W4iO', 'Cesar', 'Cochon', NULL, 'Architectural Detailer', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2025-11-28 01:05:09'),
(107, NULL, 'ced.calma', 'cedrickarnigo17@gmail.com', 'cedrickarnigo17@gmail.com', '$2y$10$MK/o93IR1zX./QeXldvSSOQ8bHoknoz7DhMb92NhTM3j.AEgmCZAu', 'ced', 'calma', 'IT', 'IT', '09938642974', 0, 'employee', 'terminated', NULL, NULL, NULL, '2025-11-28 01:45:43'),
(108, NULL, 'resty.manago', 'rjmnazareno.student@ua.edu.ph', 'rjmnazareno.student@ua.edu.ph', '$2y$10$pvxRLTVu0ZcQHpCyosRmGO3glPDKLJbWo.9vs1FOEzEzJxixuY.ay', 'Resty', 'Manago', 'IT', 'Webdev', 'N/A', 0, 'employee', 'active', NULL, 'cHdlyYoq-Sy2Gm6j4oWB2G:APA91bEJ8u_immYTrJckhNR_6ADo22gH3DHhSiMVVUtP3lEJ6uH6u1Lfr6uZR_hcvauZvtW6QqkaGAHw_fwpQocQA-p8KJMlt25VzMybkJieFg2UojtiN2I', NULL, '2025-11-28 02:14:12');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `type` enum('ticket_assigned','ticket_updated','ticket_resolved','ticket_created','comment_added','status_changed','priority_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `employee_id`, `type`, `title`, `message`, `ticket_id`, `related_user_id`, `is_read`, `created_at`) VALUES
(3, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'Ticket #3 - Network issue has been assigned', 3, NULL, 1, '2025-10-09 01:50:54'),
(4, 2, NULL, 'comment_added', 'Employee Replied', 'Employee added a comment to ticket #3', 3, NULL, 1, '2025-10-09 01:50:54'),
(5, 100, NULL, 'comment_added', 'IT Staff Responded', 'Mahfuzul Islam replied to your ticket', 1, NULL, 0, '2025-10-09 01:52:14'),
(6, 100, NULL, 'status_changed', 'Status Update', 'Your ticket is now: In Progress', 1, NULL, 0, '2025-10-09 01:52:14'),
(7, 100, NULL, 'ticket_resolved', 'Ticket Resolved', 'Your network issue has been resolved', 1, NULL, 1, '2025-10-09 01:52:14'),
(12, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'A new support ticket requires attention', 1, NULL, 1, '2025-10-09 07:45:43'),
(13, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'Employee submitted a new urgent ticket', 2, NULL, 1, '2025-10-09 05:45:43'),
(14, 1, NULL, 'ticket_updated', 'Ticket Updated by Employee', 'Employee added more information to ticket #1', 1, NULL, 1, '2025-10-09 03:45:43'),
(15, 1, NULL, 'ticket_assigned', 'Ticket Auto-Assigned', 'Ticket #1 was assigned to IT Staff', 1, 2, 1, '2025-10-08 09:45:43'),
(16, 2, NULL, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #1 \"Laptop WiFi Issue\" has been assigned to you', 1, 1, 1, '2025-10-09 06:45:43'),
(17, 2, NULL, 'ticket_assigned', 'New Ticket Assigned', 'Urgent ticket #2 assigned to you', 2, 1, 1, '2025-10-09 04:45:43'),
(18, 2, NULL, 'comment_added', 'Employee Response', 'Employee responded to your solution on ticket #1', 1, NULL, 1, '2025-10-08 09:45:43'),
(24, 0, 1, 'ticket_created', 'Ticket Submitted Successfully', 'Your support ticket has been received and will be reviewed shortly', 1, NULL, 1, '2025-10-07 10:03:21'),
(25, 0, 1, 'ticket_assigned', 'Ticket Assigned to Support Team', 'Your ticket has been assigned to our IT support team', 1, 2, 1, '2025-10-08 10:03:21'),
(26, 0, 1, 'status_changed', 'Ticket Status Updated', 'Your ticket status changed to: In Progress', 1, 2, 1, '2025-10-09 02:03:21'),
(27, 0, 1, 'comment_added', 'New Comment from IT Support', 'IT Staff replied to your ticket with more information', 1, 2, 1, '2025-10-09 06:03:21'),
(28, 0, 1, 'ticket_resolved', 'Ticket Resolved', 'Your support ticket has been marked as resolved', 1, 2, 1, '2025-10-09 09:03:21'),
(29, NULL, 1, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-6077 has been submitted and is awaiting review', 10, NULL, 1, '2025-10-14 07:32:31'),
(31, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6077: Ced', 10, NULL, 1, '2025-10-14 07:32:31'),
(32, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6077: Ced', 10, NULL, 1, '2025-10-14 07:32:31'),
(33, NULL, 1, 'status_changed', 'Ticket Status Updated', 'Your ticket #TKT-2025-6077 status changed to: In Progress', 10, 1, 1, '2025-10-14 07:33:13'),
(34, 4, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-6077: Ced', 10, 1, 1, '2025-10-14 07:33:13'),
(35, NULL, 1, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-4169 has been submitted and is awaiting review', 11, NULL, 1, '2025-10-14 09:06:40'),
(36, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4169: terst', 11, NULL, 1, '2025-10-14 09:06:40'),
(37, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4169: terst', 11, NULL, 1, '2025-10-14 09:06:40'),
(38, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4169: terst', 11, NULL, 1, '2025-10-14 09:06:40'),
(39, NULL, 1, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-4567 has been created on your behalf by admin', 13, 1, 1, '2025-10-16 02:35:40'),
(40, 4, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-4567 for employee', 13, 1, 1, '2025-10-16 02:35:40'),
(41, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-4567 for employee', 13, 1, 1, '2025-10-16 02:35:40'),
(42, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-4567: Harley Problem', 13, 1, 1, '2025-10-16 02:36:36'),
(43, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-4567 status changed to: Closed', 13, 1, 1, '2025-10-16 02:44:24'),
(44, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-4567: Harley Problem', 13, 1, 1, '2025-10-16 02:44:24'),
(45, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9752 status changed to: Closed', 12, 1, 0, '2025-10-16 08:39:20'),
(46, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9752: Test', 12, 1, 1, '2025-10-16 08:39:20'),
(47, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9752 status changed to: Resolved', 12, 1, 0, '2025-10-16 08:39:40'),
(48, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9752: Test', 12, 1, 1, '2025-10-16 08:39:40'),
(49, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9752 status changed to: Closed', 12, 2, 0, '2025-10-16 09:43:49'),
(50, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9752: Test', 12, 2, 1, '2025-10-16 09:43:49'),
(51, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-003', 3, 2, 0, '2025-10-16 09:44:05'),
(52, NULL, 1, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-7354 has been created on your behalf by admin', 16, 4, 0, '2025-10-17 07:58:42'),
(53, 2, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7354: tesrt434343', 16, 4, 1, '2025-10-17 07:58:42'),
(54, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-7354 for employee', 16, 4, 0, '2025-10-17 07:58:42'),
(55, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-7354', 16, 2, 0, '2025-10-17 07:59:23'),
(56, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-7354', 16, 2, 0, '2025-10-17 07:59:31'),
(57, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-7354', 16, 2, 0, '2025-10-17 08:33:08'),
(58, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-7354 status changed to: Resolved', 16, 2, 0, '2025-10-17 08:35:17'),
(59, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7354: tesrt434343', 16, 2, 1, '2025-10-17 08:35:17'),
(60, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-7354 status changed to: Closed', 16, 2, 0, '2025-10-17 08:35:23'),
(61, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7354: tesrt434343', 16, 2, 1, '2025-10-17 08:35:23'),
(62, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9398 status changed to: Closed', 15, 2, 0, '2025-10-17 08:43:33'),
(63, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9398: URGENT TEST - Printer not working', 15, 2, 1, '2025-10-17 08:43:33'),
(64, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-11-03-01591', 17, 2, 0, '2025-11-03 02:01:16'),
(65, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-11-03-01591 status changed to: Closed', 17, 2, 0, '2025-11-03 02:01:29'),
(66, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-11-03-01591: 🧪 SLA TEST - Quick Response Test', 17, 2, 0, '2025-11-03 02:01:29'),
(67, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-11-03-01591: 🧪 SLA TEST - Quick Response Test', 17, 2, 0, '2025-11-03 02:01:35'),
(68, NULL, 1, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-9243 has been created on your behalf by admin', 22, 4, 0, '2025-11-03 02:04:08'),
(69, 2, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9243: test notif', 22, 4, 0, '2025-11-03 02:04:08'),
(70, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-9243 for employee', 22, 4, 0, '2025-11-03 02:04:08'),
(71, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9243 status changed to: Closed', 22, 4, 0, '2025-11-03 02:18:43'),
(72, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9243: test notif', 22, 4, 0, '2025-11-03 02:18:43'),
(73, NULL, 2, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-2147 has been created on your behalf by admin', 1, 4, 0, '2025-11-05 06:24:50'),
(74, 2, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-2147: test', 1, 4, 0, '2025-11-05 06:24:50'),
(75, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-2147 for employee', 1, 4, 0, '2025-11-05 06:24:50'),
(76, NULL, 82, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-4241 has been submitted and is awaiting review', 2, NULL, 0, '2025-11-05 07:14:50'),
(77, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4241: ang daming sinasabi ng it', 2, NULL, 1, '2025-11-05 07:14:50'),
(78, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4241: ang daming sinasabi ng it', 2, NULL, 0, '2025-11-05 07:14:50'),
(79, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4241: ang daming sinasabi ng it', 2, NULL, 0, '2025-11-05 07:14:50'),
(80, NULL, 82, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-7558 has been submitted and is awaiting review', 3, NULL, 0, '2025-11-05 07:16:54'),
(81, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7558: Design', 3, NULL, 1, '2025-11-05 07:16:54'),
(82, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7558: Design', 3, NULL, 0, '2025-11-05 07:16:54'),
(83, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7558: Design', 3, NULL, 0, '2025-11-05 07:16:54'),
(84, NULL, 81, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-5894 has been created on your behalf by admin', 4, 4, 0, '2025-11-05 07:36:47'),
(85, 4, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-5894: Time Check', 4, 4, 1, '2025-11-05 07:36:47'),
(86, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-5894 for employee', 4, 4, 0, '2025-11-05 07:36:47'),
(87, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-5894 for employee', 4, 4, 0, '2025-11-05 07:36:47'),
(88, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-6528 has been submitted and is awaiting review', 5, NULL, 0, '2025-11-17 08:53:45'),
(89, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6528: test', 5, NULL, 1, '2025-11-17 08:53:45'),
(90, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6528: test', 5, NULL, 0, '2025-11-17 08:53:45'),
(91, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6528: test', 5, NULL, 0, '2025-11-17 08:53:45'),
(92, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-7157 has been submitted and is awaiting review', 6, NULL, 0, '2025-11-17 09:03:40'),
(93, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7157: test', 6, NULL, 1, '2025-11-17 09:03:40'),
(94, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7157: test', 6, NULL, 0, '2025-11-17 09:03:40'),
(95, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7157: test', 6, NULL, 0, '2025-11-17 09:03:40'),
(96, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-8346 has been submitted and is awaiting review', 7, NULL, 0, '2025-11-17 09:06:00'),
(97, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8346: test', 7, NULL, 1, '2025-11-17 09:06:00'),
(98, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8346: test', 7, NULL, 0, '2025-11-17 09:06:00'),
(99, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8346: test', 7, NULL, 0, '2025-11-17 09:06:00'),
(100, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-0868 has been submitted and is awaiting review', 8, NULL, 0, '2025-11-17 09:22:29'),
(101, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-0868: test', 8, NULL, 1, '2025-11-17 09:22:29'),
(102, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-0868: test', 8, NULL, 0, '2025-11-17 09:22:29'),
(103, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-0868: test', 8, NULL, 0, '2025-11-17 09:22:29'),
(104, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-4659 has been submitted and is awaiting review', 9, NULL, 0, '2025-11-17 09:27:56'),
(105, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4659: test', 9, NULL, 1, '2025-11-17 09:27:56'),
(106, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4659: test', 9, NULL, 0, '2025-11-17 09:27:56'),
(107, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4659: test', 9, NULL, 0, '2025-11-17 09:27:56'),
(108, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-7723 has been submitted and is awaiting review', 10, NULL, 0, '2025-11-17 09:28:57'),
(109, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7723: ced beta test', 10, NULL, 1, '2025-11-17 09:28:57'),
(110, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7723: ced beta test', 10, NULL, 0, '2025-11-17 09:28:57'),
(111, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-7723: ced beta test', 10, NULL, 0, '2025-11-17 09:28:57'),
(112, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-6238 has been submitted and is awaiting review', 11, NULL, 0, '2025-11-17 09:32:48'),
(113, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6238: notif test', 11, NULL, 1, '2025-11-17 09:32:48'),
(114, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6238: notif test', 11, NULL, 0, '2025-11-17 09:32:48'),
(115, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6238: notif test', 11, NULL, 0, '2025-11-17 09:32:48'),
(116, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-3018 has been submitted and is awaiting review', 12, NULL, 0, '2025-11-17 09:34:10'),
(117, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-3018: test', 12, NULL, 1, '2025-11-17 09:34:10'),
(118, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-3018: test', 12, NULL, 0, '2025-11-17 09:34:10'),
(119, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-3018: test', 12, NULL, 0, '2025-11-17 09:34:10'),
(120, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-5174 has been submitted and is awaiting review', 13, NULL, 0, '2025-11-17 09:35:12'),
(121, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-5174: test', 13, NULL, 1, '2025-11-17 09:35:12'),
(122, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-5174: test', 13, NULL, 0, '2025-11-17 09:35:12'),
(123, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-5174: test', 13, NULL, 0, '2025-11-17 09:35:12'),
(124, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-1620 has been submitted and is awaiting review', 14, NULL, 0, '2025-11-17 09:40:36'),
(125, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-1620: test', 14, NULL, 1, '2025-11-17 09:40:36'),
(126, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-1620: test', 14, NULL, 0, '2025-11-17 09:40:36'),
(127, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-1620: test', 14, NULL, 0, '2025-11-17 09:40:36'),
(128, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-8180 has been submitted and is awaiting review', 15, NULL, 0, '2025-11-17 09:41:49'),
(129, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8180: test', 15, NULL, 1, '2025-11-17 09:41:49'),
(130, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8180: test', 15, NULL, 0, '2025-11-17 09:41:49'),
(131, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8180: test', 15, NULL, 0, '2025-11-17 09:41:49'),
(132, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-2036 has been submitted and is awaiting review', 16, NULL, 0, '2025-11-17 09:44:35'),
(133, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-2036: test', 16, NULL, 1, '2025-11-17 09:44:35'),
(134, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-2036: test', 16, NULL, 0, '2025-11-17 09:44:35'),
(135, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-2036: test', 16, NULL, 0, '2025-11-17 09:44:35'),
(136, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-9182 has been submitted and is awaiting review', 17, NULL, 0, '2025-11-17 09:45:15'),
(137, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-9182: test', 17, NULL, 1, '2025-11-17 09:45:15'),
(138, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-9182: test', 17, NULL, 0, '2025-11-17 09:45:15'),
(139, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-9182: test', 17, NULL, 0, '2025-11-17 09:45:15'),
(140, NULL, 83, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-7283 has been created on your behalf by admin', 18, 4, 0, '2025-11-18 01:06:02'),
(141, 4, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7283: Test', 18, 4, 1, '2025-11-18 01:06:02'),
(142, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-7283 for employee', 18, 4, 0, '2025-11-18 01:06:02'),
(143, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-7283 for employee', 18, 4, 0, '2025-11-18 01:06:02'),
(144, NULL, 83, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-7777 has been created on your behalf by admin', 19, 4, 0, '2025-11-18 01:09:48'),
(145, 1, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7777: test', 19, 4, 0, '2025-11-18 01:09:48'),
(146, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-7777 for employee', 19, 4, 0, '2025-11-18 01:09:48'),
(147, NULL, 83, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-3544 has been created on your behalf by admin', 20, 4, 0, '2025-11-18 01:12:02'),
(148, 4, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-3544: test', 20, 4, 1, '2025-11-18 01:12:02'),
(149, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-3544 for employee', 20, 4, 0, '2025-11-18 01:12:02'),
(150, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-3544 for employee', 20, 4, 0, '2025-11-18 01:12:02'),
(151, NULL, 83, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-3544', 20, 4, 0, '2025-11-18 01:42:37'),
(152, NULL, 83, 'status_changed', 'Ticket Status Updated', 'Your ticket #TKT-2025-3544 status changed to: In Progress', 20, 4, 0, '2025-11-18 01:42:55'),
(153, 4, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-3544: test', 20, 4, 1, '2025-11-18 01:42:55'),
(154, NULL, 83, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-3544 status changed to: Resolved', 20, 4, 0, '2025-11-18 05:00:50'),
(155, 4, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-3544: test', 20, 4, 1, '2025-11-18 05:00:50'),
(156, NULL, 108, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-4892 has been created on your behalf by admin', 21, 4, 1, '2025-11-28 02:17:34'),
(157, 1, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-4892: test', 21, 4, 0, '2025-11-28 02:17:34'),
(158, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-4892 for employee', 21, 4, 0, '2025-11-28 02:17:34'),
(159, NULL, 108, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-4892', 21, 4, 1, '2025-11-28 02:18:40'),
(160, NULL, 108, 'status_changed', 'Ticket Status Updated', 'Your ticket #TKT-2025-4892 status changed to: In Progress', 21, 4, 0, '2025-11-28 02:18:49'),
(161, 1, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-4892: test', 21, 4, 0, '2025-11-28 02:18:50'),
(162, NULL, 81, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-8253 has been submitted and is awaiting review', 22, NULL, 0, '2025-11-28 03:17:55'),
(163, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8253: Email', 22, NULL, 1, '2025-11-28 03:17:55'),
(164, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8253: Email', 22, NULL, 0, '2025-11-28 03:17:55'),
(165, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-8253: Email', 22, NULL, 0, '2025-11-28 03:17:55'),
(166, NULL, 81, 'status_changed', 'Ticket Status Updated', 'Your ticket #TKT-2025-8253 status changed to: In Progress', 22, 4, 0, '2025-11-28 03:20:24'),
(167, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-8253: Email', 22, 4, 0, '2025-11-28 03:20:24'),
(168, 5, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-8253: Email', 22, 4, 0, '2025-11-28 03:25:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_type` enum('employee','user') NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sla_breaches`
--

CREATE TABLE `sla_breaches` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sla_tracking_id` int(11) NOT NULL,
  `breach_type` enum('response','resolution') NOT NULL,
  `target_time` datetime NOT NULL COMMENT 'When it should have been completed',
  `actual_time` datetime NOT NULL COMMENT 'When it was actually completed',
  `delay_minutes` int(11) NOT NULL COMMENT 'How many minutes late',
  `notified` tinyint(1) DEFAULT 0 COMMENT 'Has notification been sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sla_policies`
--

CREATE TABLE `sla_policies` (
  `id` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `response_time` int(11) NOT NULL COMMENT 'Minutes until first response required',
  `resolution_time` int(11) NOT NULL COMMENT 'Minutes until resolution required',
  `is_business_hours` tinyint(1) DEFAULT 1 COMMENT '1=business hours only, 0=24/7 calculation',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Enable/disable this policy',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sla_policies`
--

INSERT INTO `sla_policies` (`id`, `priority`, `response_time`, `resolution_time`, `is_business_hours`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'urgent', 15, 240, 0, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20'),
(2, 'high', 30, 480, 1, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20'),
(3, 'medium', 120, 1440, 1, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20'),
(4, 'low', 480, 2880, 1, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `sla_tracking`
--

CREATE TABLE `sla_tracking` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sla_policy_id` int(11) NOT NULL,
  `response_due_at` datetime NOT NULL COMMENT 'When first response is due',
  `first_response_at` datetime DEFAULT NULL COMMENT 'When IT staff first responded',
  `response_sla_status` enum('met','at_risk','breached','pending') DEFAULT 'pending',
  `response_time_minutes` int(11) DEFAULT NULL COMMENT 'Actual response time in minutes',
  `resolution_due_at` datetime NOT NULL COMMENT 'When resolution is due',
  `resolved_at` datetime DEFAULT NULL COMMENT 'When ticket was resolved',
  `resolution_sla_status` enum('met','at_risk','breached','pending') DEFAULT 'pending',
  `resolution_time_minutes` int(11) DEFAULT NULL COMMENT 'Actual resolution time in minutes',
  `is_paused` tinyint(1) DEFAULT 0 COMMENT 'Is SLA currently paused',
  `paused_at` datetime DEFAULT NULL COMMENT 'When SLA was paused',
  `pause_reason` varchar(255) DEFAULT NULL COMMENT 'Why SLA was paused',
  `total_pause_minutes` int(11) DEFAULT 0 COMMENT 'Total time SLA has been paused',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `sla_status` enum('met','at_risk','breached','pending','none') DEFAULT 'pending' COMMENT 'Overall SLA status for quick filtering',
  `status` enum('pending','open','in_progress','resolved','closed') NOT NULL DEFAULT 'pending',
  `submitter_id` int(11) NOT NULL,
  `submitter_type` enum('employee','user') NOT NULL DEFAULT 'employee',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `title`, `description`, `category_id`, `priority`, `sla_status`, `status`, `submitter_id`, `submitter_type`, `assigned_to`, `resolution`, `attachments`, `created_at`, `updated_at`, `resolved_at`, `closed_at`) VALUES
(1, 'TKT-2025-2147', 'test', 'test', 5, 'medium', 'pending', 'pending', 2, 'employee', 2, NULL, NULL, '2025-11-05 06:24:50', '2025-11-05 06:24:50', NULL, NULL),
(2, 'TKT-2025-4241', 'ang daming sinasabi ng it', 'ang daming sinasabi ng it', 6, 'urgent', 'pending', 'pending', 82, 'employee', NULL, NULL, NULL, '2025-11-05 07:14:50', '2025-11-05 07:14:50', NULL, NULL),
(3, 'TKT-2025-7558', 'Design', 'Di pantay yung design.', 6, 'urgent', 'pending', 'pending', 82, 'employee', NULL, NULL, '1762327014_Screenshot 2025-11-05 151550.png', '2025-11-05 07:16:54', '2025-11-05 07:16:54', NULL, NULL),
(4, 'TKT-2025-5894', 'Time Check', 'test', 2, 'low', 'pending', 'pending', 81, 'employee', 4, NULL, NULL, '2025-11-05 07:36:47', '2025-11-05 07:36:47', NULL, NULL),
(5, 'TKT-2025-6528', 'test', 'There is someone send me a link that is not from our department', 4, 'medium', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 08:53:45', '2025-11-17 08:53:45', NULL, NULL),
(6, 'TKT-2025-7157', 'test', 'There is someone send me a link that is not from our department', 4, 'medium', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:03:40', '2025-11-17 09:03:40', NULL, NULL),
(7, 'TKT-2025-8346', 'test', 'There is someone send me a link that is not from our department', 4, 'medium', 'at_risk', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:06:00', '2025-11-17 09:06:00', NULL, NULL),
(8, 'TKT-2025-0868', 'test', 'There is someone send me a link that is not from our department', 4, 'medium', 'at_risk', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:22:29', '2025-11-17 09:22:29', NULL, NULL),
(9, 'TKT-2025-4659', 'test', 'There is someone send me a link that is not from our department', 4, 'medium', 'at_risk', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:27:56', '2025-11-17 09:27:56', NULL, NULL),
(10, 'TKT-2025-7723', 'ced beta test', 'I need to update my roofing software', 2, 'low', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:28:57', '2025-11-17 09:28:57', NULL, NULL),
(11, 'TKT-2025-6238', 'notif test', 'test', 1, 'low', 'at_risk', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:32:48', '2025-11-17 09:32:48', NULL, NULL),
(12, 'TKT-2025-3018', 'test', 'test', 5, 'medium', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:34:10', '2025-11-17 09:34:10', NULL, NULL),
(13, 'TKT-2025-5174', 'test', 'test', 4, 'medium', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:35:12', '2025-11-17 09:35:12', NULL, NULL),
(14, 'TKT-2025-1620', 'test', 'test', 5, 'medium', 'pending', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:40:36', '2025-11-17 09:40:36', NULL, NULL),
(15, 'TKT-2025-8180', 'test', 'test', 6, 'medium', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:41:49', '2025-11-17 09:41:49', NULL, NULL),
(16, 'TKT-2025-2036', 'test', 'test', 4, 'medium', 'breached', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:44:35', '2025-11-17 09:44:35', NULL, NULL),
(17, 'TKT-2025-9182', 'test', 'test', 4, 'medium', 'met', 'pending', 81, 'employee', NULL, NULL, NULL, '2025-11-17 09:45:15', '2025-11-17 09:45:15', NULL, NULL),
(18, 'TKT-2025-7283', 'Test', 'test', 5, 'medium', 'pending', 'pending', 83, 'employee', 4, NULL, NULL, '2025-11-18 01:06:02', '2025-11-18 01:06:02', NULL, NULL),
(19, 'TKT-2025-7777', 'test', 'test', 5, 'medium', 'pending', 'pending', 83, 'employee', 1, NULL, NULL, '2025-11-18 01:09:48', '2025-11-18 01:09:48', NULL, NULL),
(20, 'TKT-2025-3544', 'test', 'test', 5, 'medium', 'met', 'resolved', 83, 'employee', 4, NULL, NULL, '2025-11-18 01:12:02', '2025-11-18 05:00:52', '2025-11-18 05:00:52', NULL),
(21, 'TKT-2025-4892', 'test', 'test', 7, 'urgent', 'breached', 'in_progress', 108, 'employee', 1, NULL, NULL, '2025-11-28 02:17:34', '2025-11-28 02:18:53', NULL, NULL),
(22, 'TKT-2025-8253', 'Email', 'Can&#039;t access the email', 4, 'medium', 'met', 'in_progress', 81, 'employee', NULL, NULL, NULL, '2025-11-28 03:17:55', '2025-11-28 03:25:17', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_activity`
--

CREATE TABLE `ticket_activity` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('employee','user') NOT NULL DEFAULT 'user',
  `action_type` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_activity`
--

INSERT INTO `ticket_activity` (`id`, `ticket_id`, `user_id`, `user_type`, `action_type`, `old_value`, `new_value`, `comment`, `created_at`) VALUES
(1, 1, 1, 'employee', 'created', NULL, 'open', 'Ticket created', '2025-10-08 03:37:59'),
(2, 1, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to IT staff', '2025-10-08 03:37:59'),
(3, 2, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-08 03:37:59'),
(4, 3, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-08 03:37:59'),
(5, 3, 2, 'user', 'status_change', NULL, 'in_progress', 'Started working on this issue', '2025-10-08 03:37:59'),
(6, 4, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-08 03:37:59'),
(7, 4, 2, 'user', 'status_change', NULL, 'resolved', 'Email configured successfully', '2025-10-08 03:37:59'),
(8, 1, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-08 03:57:13'),
(9, 1, 1, 'user', 'status_change', 'open', 'pending', 'Status changed from open to pending', '2025-10-08 03:57:20'),
(10, 1, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-08 04:47:03'),
(11, 4, 1, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-08 04:54:15'),
(12, 4, 1, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-08 04:57:11'),
(13, 4, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-08 04:57:11'),
(14, 3, 1, 'user', 'status_change', 'in_progress', 'closed', 'Status changed from in_progress to closed', '2025-10-08 04:57:42'),
(15, 2, 1, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-10-08 04:57:55'),
(16, 2, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-08 04:57:55'),
(17, 1, 1, 'user', 'status_change', 'open', 'closed', 'Status changed from open to closed', '2025-10-08 04:58:15'),
(18, 1, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-08 04:58:15'),
(19, 1, 1, 'employee', 'comment', NULL, NULL, 'test', '2025-10-08 04:58:42'),
(20, 1, 1, 'employee', 'comment', NULL, NULL, 'ced', '2025-10-08 04:58:52'),
(21, 1, 1, 'employee', 'comment', NULL, NULL, 'ced', '2025-10-08 04:58:57'),
(22, 5, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-09 02:32:42'),
(23, 5, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-09 02:34:05'),
(24, 5, 1, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-10-09 02:34:19'),
(25, 5, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-09 02:34:19'),
(26, 6, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-09 09:38:01'),
(27, 6, 1, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-10-09 09:38:41'),
(28, 6, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-09 09:38:41'),
(29, 6, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-10 03:55:22'),
(30, 7, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 05:58:49'),
(31, 8, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 06:35:06'),
(32, 9, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 07:01:43'),
(33, 10, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 07:32:31'),
(34, 10, 1, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-10-14 07:33:13'),
(35, 10, 1, 'user', 'assigned', NULL, 'Cedrick', 'Ticket assigned to Cedrick', '2025-10-14 07:33:13'),
(36, 11, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 09:06:40'),
(37, 12, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-10-16 02:25:54'),
(38, 13, 1, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-10-16 02:35:40'),
(39, 13, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 02:36:36'),
(40, 13, 1, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-10-16 02:44:24'),
(41, 13, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 02:44:24'),
(42, 12, 1, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-10-16 08:39:20'),
(43, 12, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 08:39:20'),
(44, 12, 1, 'user', 'status_change', 'closed', 'resolved', 'Status changed from closed to resolved', '2025-10-16 08:39:40'),
(45, 12, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 08:39:40'),
(46, 12, 2, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-16 09:43:49'),
(47, 12, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 09:43:49'),
(48, 3, 2, 'user', 'comment', NULL, NULL, 'test', '2025-10-16 09:44:05'),
(49, 16, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-10-17 07:58:42'),
(50, 16, 2, 'user', 'comment', NULL, NULL, 'hello', '2025-10-17 07:59:22'),
(51, 16, 2, 'user', 'comment', NULL, NULL, 'hello', '2025-10-17 07:59:31'),
(52, 16, 2, 'user', 'comment', NULL, NULL, 'test', '2025-10-17 08:33:08'),
(53, 16, 2, 'user', 'status_change', 'pending', 'resolved', 'Status changed from pending to resolved', '2025-10-17 08:35:17'),
(54, 16, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-17 08:35:17'),
(55, 16, 2, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-17 08:35:23'),
(56, 16, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-17 08:35:23'),
(57, 15, 2, 'user', 'status_change', 'open', 'closed', 'Status changed from open to closed', '2025-10-17 08:43:33'),
(58, 15, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-17 08:43:33'),
(59, 17, 2, 'user', 'comment', NULL, NULL, 'hi', '2025-11-03 02:01:16'),
(60, 17, 2, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-11-03 02:01:29'),
(61, 17, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-11-03 02:01:29'),
(62, 17, 2, 'user', 'resolution_added', NULL, 'test', 'Resolution added', '2025-11-03 02:01:29'),
(63, 17, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-11-03 02:01:35'),
(64, 17, 2, 'user', 'resolution_added', NULL, 'test', 'Resolution added', '2025-11-03 02:01:35'),
(65, 22, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-03 02:04:08'),
(66, 22, 4, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-11-03 02:18:43'),
(67, 22, 4, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-11-03 02:18:43'),
(68, 1, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-05 06:24:50'),
(69, 2, 82, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-05 07:14:50'),
(70, 3, 82, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-05 07:16:54'),
(71, 4, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-05 07:36:47'),
(72, 5, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 08:53:45'),
(73, 6, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:03:40'),
(74, 7, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:06:00'),
(75, 8, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:22:29'),
(76, 9, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:27:56'),
(77, 10, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:28:57'),
(78, 11, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:32:48'),
(79, 12, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:34:10'),
(80, 13, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:35:12'),
(81, 14, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:40:36'),
(82, 15, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:41:49'),
(83, 16, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:44:35'),
(84, 17, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-17 09:45:15'),
(85, 18, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-18 01:06:02'),
(86, 19, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-18 01:09:48'),
(87, 20, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-18 01:12:02'),
(88, 20, 4, 'user', 'comment', NULL, NULL, 'test', '2025-11-18 01:42:37'),
(89, 20, 4, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-11-18 01:42:55'),
(90, 20, 4, 'user', 'assigned', NULL, 'Cedrick', 'Ticket assigned to Cedrick', '2025-11-18 01:42:55'),
(91, 20, 4, 'user', 'status_change', 'in_progress', 'resolved', 'Status changed from in_progress to resolved', '2025-11-18 05:00:50'),
(92, 20, 4, 'user', 'assigned', NULL, 'Cedrick', 'Ticket assigned to Cedrick', '2025-11-18 05:00:50'),
(93, 21, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-28 02:17:34'),
(94, 21, 4, 'user', 'comment', NULL, NULL, 'hi', '2025-11-28 02:18:40'),
(95, 21, 4, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-11-28 02:18:49'),
(96, 21, 4, 'user', 'assigned', NULL, 'Cedrick C. Arnigo', 'Ticket assigned to Cedrick C. Arnigo', '2025-11-28 02:18:50'),
(97, 22, 81, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-11-28 03:17:55'),
(98, 22, 4, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-11-28 03:20:24'),
(99, 22, 4, 'user', 'assigned', NULL, 'Resty James Nazareno', 'Ticket assigned to Resty James Nazareno', '2025-11-28 03:20:24'),
(100, 22, 4, 'user', 'assigned', NULL, 'Ced', 'Ticket assigned to Ced', '2025-11-28 03:25:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('it_staff','admin') NOT NULL DEFAULT 'it_staff',
  `department` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fcm_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `department`, `phone`, `is_active`, `created_at`, `updated_at`, `fcm_token`) VALUES
(1, 'admin', 'admin@company.com', '$2y$10$N0waCj/5zu6MKllawHUlTOc9K5evXFsLsyuv4V0TXBjk6/cncSGUq', 'Cedrick C. Arnigo', 'admin', 'IT', '', 1, '2025-10-08 03:37:59', '2025-10-10 02:51:18', NULL),
(2, 'Resty.James', 'rjmanago@gmail.com', '$2y$10$1ebSizNOgxEJJGuSCfvXBeW6Q3JoN27H.aN4LHzyS0xrLUlqlP8N2', 'Resty James Nazareno', 'admin', 'IT', '', 1, '2025-10-08 03:37:59', '2026-01-06 02:44:28', 'cO8TCfjSpK33jXYHvT23lX:APA91bHz4miYlECcQEY3S2uUvUleWeOfAHbs5--ueef7NZDB33Fxdd_-vT-xoKe-jUHyJPHDgntFCOe-4VbDw8GapPjC5inthNYsBNcgYGaV39lv-9u0lUo'),
(4, 'Cedrick.Arnigo', 'cedrick.arnigo@resourcestaff.com.ph', '$2y$10$L.zldWBya5.sIW7DOCrXP.KZt8S5ula/3hTEfOeRR.VyEHXSjXNVC', 'Cedrick', 'admin', 'IT Department', '993 864 2974', 1, '2025-10-13 04:12:29', '2026-01-06 02:43:24', 'cHdlyYoq-Sy2Gm6j4oWB2G:APA91bEJ8u_immYTrJckhNR_6ADo22gH3DHhSiMVVUtP3lEJ6uH6u1Lfr6uZR_hcvauZvtW6QqkaGAHw_fwpQocQA-p8KJMlt25VzMybkJieFg2UojtiN2I'),
(6, 'RSS_Admin', 'Tina.Pangan@resourcestaff.com.ph', '$2y$10$C36PODFUCAd9gg.otncuzuKt3unU9m2drjAu.CK74XzKFp2dotAXe', 'RSS Admin', 'admin', 'RSS', NULL, 1, '2025-12-29 00:24:38', '2026-01-06 02:44:40', 'f2D_zImE4HQJ6d-3JRXq3u:APA91bFo995nRg2hu413Tf3QBWld8ZJVTKtUJ-MFIe1Fc4Vj_fDGnzt0VPu61D5mJFWo-TkprkU0GLwkES2bcbeU5rpDjwijZppLTwut44W6mNWFw528HdA');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sla_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_sla_summary` (
`ticket_id` int(11)
,`ticket_number` varchar(20)
,`title` varchar(200)
,`priority` enum('low','medium','high','urgent')
,`ticket_status` enum('pending','open','in_progress','resolved','closed')
,`sla_status` enum('met','at_risk','breached','pending','none')
,`response_sla_status` enum('met','at_risk','breached','pending')
,`resolution_sla_status` enum('met','at_risk','breached','pending')
,`response_due_at` datetime
,`resolution_due_at` datetime
,`first_response_at` datetime
,`resolved_at` datetime
,`response_time_minutes` int(11)
,`resolution_time_minutes` int(11)
,`is_paused` tinyint(1)
,`target_response_minutes` int(11)
,`target_resolution_minutes` int(11)
,`is_business_hours` tinyint(1)
,`minutes_remaining` bigint(21)
,`elapsed_percentage` decimal(26,2)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_fcm_token_employees` (`fcm_token`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_notifications_employee_id` (`employee_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email_type` (`user_email`,`user_type`),
  ADD KEY `idx_token_expires` (`token`,`expires_at`);

--
-- Indexes for table `sla_breaches`
--
ALTER TABLE `sla_breaches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sla_tracking_id` (`sla_tracking_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_breach_type` (`breach_type`),
  ADD KEY `idx_notified` (`notified`);

--
-- Indexes for table `sla_policies`
--
ALTER TABLE `sla_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_priority` (`priority`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `sla_tracking`
--
ALTER TABLE `sla_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sla_policy_id` (`sla_policy_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_response_status` (`response_sla_status`),
  ADD KEY `idx_resolution_status` (`resolution_sla_status`),
  ADD KEY `idx_response_due` (`response_due_at`),
  ADD KEY `idx_resolution_due` (`resolution_due_at`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD UNIQUE KEY `unique_ticket_number` (`ticket_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_submitter` (`submitter_id`,`submitter_type`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_sla_status` (`sla_status`);

--
-- Indexes for table `ticket_activity`
--
ALTER TABLE `ticket_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_user` (`user_id`,`user_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sla_breaches`
--
ALTER TABLE `sla_breaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sla_policies`
--
ALTER TABLE `sla_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sla_tracking`
--
ALTER TABLE `sla_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `ticket_activity`
--
ALTER TABLE `ticket_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- --------------------------------------------------------

--
-- Structure for view `v_sla_summary`
--
DROP TABLE IF EXISTS `v_sla_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u816220874_AyrgoResolveIT`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_sla_summary`  AS SELECT `t`.`id` AS `ticket_id`, `t`.`ticket_number` AS `ticket_number`, `t`.`title` AS `title`, `t`.`priority` AS `priority`, `t`.`status` AS `ticket_status`, `t`.`sla_status` AS `sla_status`, `st`.`response_sla_status` AS `response_sla_status`, `st`.`resolution_sla_status` AS `resolution_sla_status`, `st`.`response_due_at` AS `response_due_at`, `st`.`resolution_due_at` AS `resolution_due_at`, `st`.`first_response_at` AS `first_response_at`, `st`.`resolved_at` AS `resolved_at`, `st`.`response_time_minutes` AS `response_time_minutes`, `st`.`resolution_time_minutes` AS `resolution_time_minutes`, `st`.`is_paused` AS `is_paused`, `sp`.`response_time` AS `target_response_minutes`, `sp`.`resolution_time` AS `target_resolution_minutes`, `sp`.`is_business_hours` AS `is_business_hours`, CASE WHEN `st`.`resolved_at` is not null THEN 0 WHEN `st`.`is_paused` = 1 THEN timestampdiff(MINUTE,current_timestamp(),`st`.`resolution_due_at`) ELSE timestampdiff(MINUTE,current_timestamp(),`st`.`resolution_due_at`) END AS `minutes_remaining`, CASE WHEN `st`.`resolved_at` is not null THEN 100 ELSE round(timestampdiff(MINUTE,`t`.`created_at`,current_timestamp()) / `sp`.`resolution_time` * 100,2) END AS `elapsed_percentage` FROM ((`tickets` `t` left join `sla_tracking` `st` on(`t`.`id` = `st`.`ticket_id`)) left join `sla_policies` `sp` on(`st`.`sla_policy_id` = `sp`.`id`)) WHERE `t`.`status` <> 'closed' ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sla_breaches`
--
ALTER TABLE `sla_breaches`
  ADD CONSTRAINT `sla_breaches_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sla_breaches_ibfk_2` FOREIGN KEY (`sla_tracking_id`) REFERENCES `sla_tracking` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sla_tracking`
--
ALTER TABLE `sla_tracking`
  ADD CONSTRAINT `sla_tracking_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sla_tracking_ibfk_2` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `ticket_activity`
--
ALTER TABLE `ticket_activity`
  ADD CONSTRAINT `ticket_activity_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
