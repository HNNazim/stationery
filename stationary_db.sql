-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2025 at 11:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stationary_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `form_data`
--

CREATE TABLE `form_data` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `Month` varchar(10) NOT NULL,
  `blue_pen` int(11) DEFAULT NULL,
  `black_pen` int(11) DEFAULT NULL,
  `red_pen` int(11) DEFAULT NULL,
  `cr_40` int(11) DEFAULT NULL,
  `cr_80` int(11) DEFAULT NULL,
  `cr_120` int(11) DEFAULT NULL,
  `cr_160` int(11) DEFAULT NULL,
  `cr_200` int(11) DEFAULT NULL,
  `a4` int(11) DEFAULT NULL,
  `a3` int(11) DEFAULT NULL,
  `a5` int(11) DEFAULT NULL,
  `b5` int(11) DEFAULT NULL,
  `legal` int(11) DEFAULT NULL,
  `halfsheet` int(11) DEFAULT NULL,
  `d_paper` int(11) DEFAULT NULL,
  `pencil` int(11) DEFAULT NULL,
  `sharpner` int(11) DEFAULT NULL,
  `eraser` int(11) DEFAULT NULL,
  `highlighter` int(11) DEFAULT NULL,
  `halfctape` int(11) DEFAULT NULL,
  `ctape1` int(11) DEFAULT NULL,
  `ctape2` int(11) DEFAULT NULL,
  `glue_l` int(11) DEFAULT NULL,
  `glue_s` int(11) DEFAULT NULL,
  `letter_heads` int(11) DEFAULT NULL,
  `cover1` int(11) DEFAULT NULL,
  `cover2` int(11) DEFAULT NULL,
  `cover3` int(11) DEFAULT NULL,
  `cover4` int(11) DEFAULT NULL,
  `ruler` int(11) DEFAULT NULL,
  `tipex` int(11) DEFAULT NULL,
  `file_tags` int(11) DEFAULT NULL,
  `note_big` int(11) DEFAULT NULL,
  `note_small` int(11) DEFAULT NULL,
  `gel_pen` int(11) DEFAULT NULL,
  `sign_pen` int(11) DEFAULT NULL,
  `ohp_pen` int(11) DEFAULT NULL,
  `m_paper` int(11) DEFAULT NULL,
  `com_sheet1` int(11) DEFAULT NULL,
  `com_sheet2` int(11) DEFAULT NULL,
  `stkpaper` int(11) DEFAULT NULL,
  `blue` int(11) DEFAULT NULL,
  `pink` int(11) DEFAULT NULL,
  `yellow` int(11) DEFAULT NULL,
  `green` int(11) DEFAULT NULL,
  `red` int(11) DEFAULT NULL,
  `orange` int(11) DEFAULT NULL,
  `spins1` int(11) DEFAULT NULL,
  `spins2` int(11) DEFAULT NULL,
  `spins3` int(11) DEFAULT NULL,
  `spins4` int(11) DEFAULT NULL,
  `spins5` int(11) DEFAULT NULL,
  `spins6` int(11) DEFAULT NULL,
  `s_remover` int(11) DEFAULT NULL,
  `minutesheet` int(11) DEFAULT NULL,
  `markerpen` int(11) DEFAULT NULL,
  `permenant_m` int(11) DEFAULT NULL,
  `pins` int(11) DEFAULT NULL,
  `d_pins` int(11) DEFAULT NULL,
  `n_clips` int(11) DEFAULT NULL,
  `b_clips` int(11) DEFAULT NULL,
  `bfile2` int(11) DEFAULT NULL,
  `bfile3` int(11) DEFAULT NULL,
  `rfile` int(11) DEFAULT NULL,
  `hfile` int(11) DEFAULT NULL,
  `tfile` int(11) DEFAULT NULL,
  `carbon1` int(11) DEFAULT NULL,
  `carbon2` int(11) DEFAULT NULL,
  `scissor` int(11) DEFAULT NULL,
  `polythene_roll` int(11) DEFAULT NULL,
  `browntape` int(11) DEFAULT NULL,
  `redtape` int(11) DEFAULT NULL,
  `blacktape` int(11) DEFAULT NULL,
  `greentape` int(11) DEFAULT NULL,
  `bluetape` int(11) DEFAULT NULL,
  `doubletape` int(11) DEFAULT NULL,
  `bindingtape` int(11) DEFAULT NULL,
  `field_notebook` int(11) DEFAULT NULL,
  `printed_cover` int(11) DEFAULT NULL,
  `non_printed` int(11) DEFAULT NULL,
  `inkpad` int(11) DEFAULT NULL,
  `i_btlS` int(11) DEFAULT NULL,
  `i_btlL` int(11) DEFAULT NULL,
  `paperfastner` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_data`
--

INSERT INTO `form_data` (`id`, `subject`, `branch`, `name`, `Month`, `blue_pen`, `black_pen`, `red_pen`, `cr_40`, `cr_80`, `cr_120`, `cr_160`, `cr_200`, `a4`, `a3`, `a5`, `b5`, `legal`, `halfsheet`, `d_paper`, `pencil`, `sharpner`, `eraser`, `highlighter`, `halfctape`, `ctape1`, `ctape2`, `glue_l`, `glue_s`, `letter_heads`, `cover1`, `cover2`, `cover3`, `cover4`, `ruler`, `tipex`, `file_tags`, `note_big`, `note_small`, `gel_pen`, `sign_pen`, `ohp_pen`, `m_paper`, `com_sheet1`, `com_sheet2`, `stkpaper`, `blue`, `pink`, `yellow`, `green`, `red`, `orange`, `spins1`, `spins2`, `spins3`, `spins4`, `spins5`, `spins6`, `s_remover`, `minutesheet`, `markerpen`, `permenant_m`, `pins`, `d_pins`, `n_clips`, `b_clips`, `bfile2`, `bfile3`, `rfile`, `hfile`, `tfile`, `carbon1`, `carbon2`, `scissor`, `polythene_roll`, `browntape`, `redtape`, `blacktape`, `greentape`, `bluetape`, `doubletape`, `bindingtape`, `field_notebook`, `printed_cover`, `non_printed`, `inkpad`, `i_btlS`, `i_btlL`, `paperfastner`, `submitted_at`) VALUES
(1, 'ACC:01', 'ACCOUNT', 'hnn', '', 1, 1, 1, 0, 0, 0, 0, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-04-10 06:24:22'),
(2, 'ACC:01', 'ACCOUNT', 'hnn', '', 1, 1, 1, 0, 0, 0, 0, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-04-10 08:00:39'),
(3, 'DEV:01', 'DEVELOPMENT', 'hnn', '', 1, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-04-10 08:45:58'),
(4, 'EST:01', 'ESTABLISHMENT', 'hnn', '', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, '2025-04-10 08:59:16'),
(5, 'EST:02', 'ESTABLISHMENT', 'hnn', '', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-04-10 09:04:13'),
(6, 'DEV:02', 'DEVELOPMENT', 'hnn', '', 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-04-10 09:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `අනු_අංකය` int(11) NOT NULL,
  `නිලධාරියාෙග්_නම` varchar(100) NOT NULL,
  `අංශය` varchar(50) NOT NULL,
  `අඩිකෝදු` int(11) NOT NULL DEFAULT 0,
  `ඇල්පෙනෙති(පැකට්)` decimal(10,2) NOT NULL,
  `OHP_Pen` int(11) NOT NULL DEFAULT current_timestamp(),
  `කටර්` int(11) DEFAULT NULL,
  `කතුරු` int(11) DEFAULT NULL,
  `ක්ලිප්ස් (පැකට්)` int(11) DEFAULT NULL,
  `කාබන් කොල(තනි පැත්ත)` int(11) DEFAULT NULL,
  `කාබන් කොල(දෙපැත්ත)` int(11) DEFAULT NULL,
  `Box Files 3"` int(10) DEFAULT NULL,
  `Box Files 2"` int(11) DEFAULT NULL,
  `Ring File` int(11) DEFAULT NULL,
  `ගම් ටේප් දුඹුරු` int(11) DEFAULT NULL,
  `ඡායා පිටපත් කඩදාසි A3 (කොල)` int(11) DEFAULT NULL,
  `ඡායා පිටපත් කඩදාසි A4 (කොල)` int(11) DEFAULT NULL,
  `ඡායා පිටපත් කඩදාසි Legal (කොල)` int(11) DEFAULT NULL,
  `ඡායා පිටපත් කඩදාසි පාට A4 (කොල)` int(11) DEFAULT NULL,
  `ඡායා පිටපත් කඩදාසි B5 (කොල)` int(11) DEFAULT NULL,
  `ජෙල් පෑන්` int(11) DEFAULT NULL,
  `ට්‍රාන්ස්පේරන්සි කොල` int(11) DEFAULT NULL,
  `ටැග්ස්` int(11) DEFAULT NULL,
  `ටිපෙක්ස්` int(11) DEFAULT NULL,
  `DVD තැටි` int(11) DEFAULT NULL,
  `ඩ්‍රෝවින් පින් (පැකට්)` int(11) DEFAULT NULL,
  `ඩිමයි කොල` int(11) DEFAULT NULL,
  `තින්ත පෑඩ්` int(11) DEFAULT NULL,
  `තින්ත බෝතල් (ලොකු)` int(11) DEFAULT NULL,
  `තින්ත බෝතල් (කුඩා)` int(11) DEFAULT NULL,
  `Note Sticker` int(11) DEFAULT NULL,
  ` 9½*11 පරි.කඩ` int(11) DEFAULT NULL,
  ` 15*11 පරි.කඩදාසි` int(11) DEFAULT NULL,
  `පැන්සල්` int(11) DEFAULT NULL,
  `Filed Note Book` int(11) DEFAULT NULL,
  `Paper fastener` int(11) DEFAULT NULL,
  `Permanet Pen` int(11) DEFAULT NULL,
  `4 Hole File` int(11) DEFAULT NULL,
  `බයින්ඩ්න් ටේප්` int(11) DEFAULT NULL,
  `බයින්ඩර් ගම් බෝතල් (200ml)` int(11) DEFAULT NULL,
  `බයින්ඩර් ගම් බෝතල් (40ml)` int(11) DEFAULT NULL,
  `බුල්ඩෝ ක්ලිප්ස්` int(11) DEFAULT NULL,
  `බෝල්පොයින්ට් පෑන්` int(11) DEFAULT NULL,
  `මකන කෑලි` int(11) DEFAULT NULL,
  `මාකර් පෙන්` int(11) DEFAULT NULL,
  `මැනිලා කඩදාසි` int(11) DEFAULT NULL,
  `රෙඩ් ටේප්` int(11) DEFAULT NULL,
  `ඉටි කොළ රෝල්` int(11) DEFAULT NULL,
  `ලිපි කවර 10*7` int(11) DEFAULT NULL,
  `ලිපිකවර 10*15(ඉන්දියන් මැනිලා)` int(11) DEFAULT NULL,
  `ලිපිකවර 9*4(ඉන්දියන් මැනිලා)` int(11) DEFAULT NULL,
  `ලිපිකවර 9*6 සුදු` int(11) DEFAULT NULL,
  `ලිපිගොනුකවර (මුද්‍රිත)` int(11) DEFAULT NULL,
  `ලිපිගොනුකවර(මුද්‍රිත නොවන)` int(11) DEFAULT NULL,
  `ලිපිශිර්ෂ(අමාත්‍යාංශය)` int(11) DEFAULT NULL,
  `ලොකු ගම් බෝතල්` int(11) DEFAULT NULL,
  `වයිරස් ගාඩ් (1 user)` int(11) DEFAULT NULL,
  `වයිරස් ගාඩ් (3 user)` int(11) DEFAULT NULL,
  `සටහන් පත්‍ර` int(11) DEFAULT NULL,
  `Signing Pen` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (24X6)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (23X8)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (23X10)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (23X17)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (23X15)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (23X20)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් කටු (23X24)` int(11) DEFAULT NULL,
  `ස්ටෙප්ලර් රිමුවර්` int(11) DEFAULT NULL,
  `සෙලෝටේප් ½` int(11) DEFAULT NULL,
  `සෙලෝටේප් අඟල් 1` int(11) DEFAULT NULL,
  `සෙලෝටේප් 2 :` int(11) DEFAULT NULL,
  `ස්ටිකර් කොල (A4 ප්‍රමාණය)` int(11) DEFAULT NULL,
  `CD  කවර` int(11) DEFAULT NULL,
  `CD තැටි` int(11) DEFAULT NULL,
  `CR I` int(11) DEFAULT NULL,
  `CR II` int(11) DEFAULT NULL,
  `CR III` int(11) DEFAULT NULL,
  `CR IV` int(11) DEFAULT NULL,
  `CR V` int(11) DEFAULT NULL,
  `හයිලයිට් පෙන්` int(11) DEFAULT NULL,
  `හාප්ෂිට්` int(11) DEFAULT NULL,
  `Trasferance File folder` int(11) DEFAULT NULL,
  `ඩබල් ටේප්` int(11) DEFAULT NULL,
  `Toshiba T 5018 P ( e - studio 2518 A printer)` int(11) DEFAULT NULL,
  `Samsun MLT-D101S/XSG Fax Toner` int(11) DEFAULT NULL,
  `Toshiba T5018P(e-studio 2518Aprinter` int(11) DEFAULT NULL,
  `canon 337 Fax Toner` int(11) DEFAULT NULL,
  `Canon IncBottle(canon pixma G1010 Printer)` int(11) DEFAULT NULL,
  `HP HGT52 Colo inkbottl` int(11) DEFAULT NULL,
  `Toshiba T-5070 Toner` int(11) DEFAULT NULL,
  `Brother TN-2360 Toner` int(11) DEFAULT NULL,
  `Epson LQ-2190 Ribbon` int(11) DEFAULT NULL,
  `Riso master roll A 3.5 S-4277` int(11) DEFAULT NULL,
  `Riso ink Z type 4251` int(11) DEFAULT NULL,
  `Canon LBP 325 Toner` int(11) DEFAULT NULL,
  `Toshiba T-2309P Toner` int(11) DEFAULT NULL,
  `HP 17a Printer Toner` int(11) DEFAULT NULL,
  `Canon LBP 226 BP Asta 057` int(11) DEFAULT NULL,
  `Epson L110 Color Bottles` int(11) DEFAULT NULL,
  `Epson LQ-300+II Ribbon` int(11) DEFAULT NULL,
  `Brother HL-6200 DN Toner` int(11) DEFAULT NULL,
  `column40` int(11) DEFAULT NULL,
  `column41` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`අනු_අංකය`, `නිලධාරියාෙග්_නම`, `අංශය`, `අඩිකෝදු`, `ඇල්පෙනෙති(පැකට්)`, `OHP_Pen`, `කටර්`, `කතුරු`, `ක්ලිප්ස් (පැකට්)`, `කාබන් කොල(තනි පැත්ත)`, `කාබන් කොල(දෙපැත්ත)`, `Box Files 3"`, `Box Files 2"`, `Ring File`, `ගම් ටේප් දුඹුරු`, `ඡායා පිටපත් කඩදාසි A3 (කොල)`, `ඡායා පිටපත් කඩදාසි A4 (කොල)`, `ඡායා පිටපත් කඩදාසි Legal (කොල)`, `ඡායා පිටපත් කඩදාසි පාට A4 (කොල)`, `ඡායා පිටපත් කඩදාසි B5 (කොල)`, `ජෙල් පෑන්`, `ට්‍රාන්ස්පේරන්සි කොල`, `ටැග්ස්`, `ටිපෙක්ස්`, `DVD තැටි`, `ඩ්‍රෝවින් පින් (පැකට්)`, `ඩිමයි කොල`, `තින්ත පෑඩ්`, `තින්ත බෝතල් (ලොකු)`, `තින්ත බෝතල් (කුඩා)`, `Note Sticker`, ` 9½*11 පරි.කඩ`, ` 15*11 පරි.කඩදාසි`, `පැන්සල්`, `Filed Note Book`, `Paper fastener`, `Permanet Pen`, `4 Hole File`, `බයින්ඩ්න් ටේප්`, `බයින්ඩර් ගම් බෝතල් (200ml)`, `බයින්ඩර් ගම් බෝතල් (40ml)`, `බුල්ඩෝ ක්ලිප්ස්`, `බෝල්පොයින්ට් පෑන්`, `මකන කෑලි`, `මාකර් පෙන්`, `මැනිලා කඩදාසි`, `රෙඩ් ටේප්`, `ඉටි කොළ රෝල්`, `ලිපි කවර 10*7`, `ලිපිකවර 10*15(ඉන්දියන් මැනිලා)`, `ලිපිකවර 9*4(ඉන්දියන් මැනිලා)`, `ලිපිකවර 9*6 සුදු`, `ලිපිගොනුකවර (මුද්‍රිත)`, `ලිපිගොනුකවර(මුද්‍රිත නොවන)`, `ලිපිශිර්ෂ(අමාත්‍යාංශය)`, `ලොකු ගම් බෝතල්`, `වයිරස් ගාඩ් (1 user)`, `වයිරස් ගාඩ් (3 user)`, `සටහන් පත්‍ර`, `Signing Pen`, `ස්ටෙප්ලර් කටු (24X6)`, `ස්ටෙප්ලර් කටු (23X8)`, `ස්ටෙප්ලර් කටු (23X10)`, `ස්ටෙප්ලර් කටු (23X17)`, `ස්ටෙප්ලර් කටු (23X15)`, `ස්ටෙප්ලර් කටු (23X20)`, `ස්ටෙප්ලර් කටු (23X24)`, `ස්ටෙප්ලර් රිමුවර්`, `සෙලෝටේප් ½`, `සෙලෝටේප් අඟල් 1`, `සෙලෝටේප් 2 :`, `ස්ටිකර් කොල (A4 ප්‍රමාණය)`, `CD  කවර`, `CD තැටි`, `CR I`, `CR II`, `CR III`, `CR IV`, `CR V`, `හයිලයිට් පෙන්`, `හාප්ෂිට්`, `Trasferance File folder`, `ඩබල් ටේප්`, `Toshiba T 5018 P ( e - studio 2518 A printer)`, `Samsun MLT-D101S/XSG Fax Toner`, `Toshiba T5018P(e-studio 2518Aprinter`, `canon 337 Fax Toner`, `Canon IncBottle(canon pixma G1010 Printer)`, `HP HGT52 Colo inkbottl`, `Toshiba T-5070 Toner`, `Brother TN-2360 Toner`, `Epson LQ-2190 Ribbon`, `Riso master roll A 3.5 S-4277`, `Riso ink Z type 4251`, `Canon LBP 325 Toner`, `Toshiba T-2309P Toner`, `HP 17a Printer Toner`, `Canon LBP 226 BP Asta 057`, `Epson L110 Color Bottles`, `Epson LQ-300+II Ribbon`, `Brother HL-6200 DN Toner`, `column40`, `column41`) VALUES
(5, '', '', 0, 0.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products_table`
--

CREATE TABLE `products_table` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `available_quantity` int(11) DEFAULT NULL,
  `distributed_quantity` int(11) DEFAULT NULL,
  `remaining_quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products_table`
--

INSERT INTO `products_table` (`product_id`, `product_name`, `available_quantity`, `distributed_quantity`, `remaining_quantity`) VALUES
(1, 'අඩිකෝදු', 50, 20, 30),
(2, 'ඇල්පෙනෙති_පැකට්', 0, 0, 0),
(3, 'OHP_Pen', 0, 0, 0),
(4, 'කටර්', 0, 0, 0),
(5, 'කතුරු', 0, 0, 0),
(6, 'ක්ලිප්ස්_පැකට්', 0, 0, 0),
(7, 'කාබන්_කොල_තනි_පැත්ත', 0, 0, 0),
(8, 'කාබන්_කොල_දෙපැත්ත', 0, 0, 0),
(9, 'Box_Files_3', 0, 0, 0),
(10, 'Box_Files_2', 0, 0, 0),
(11, 'Ring_File', 0, 0, 0),
(12, 'ගම්ටේප්_දුඹුරු', 0, 0, 0),
(14, 'A3_කොල', 0, 0, 0),
(15, 'A4_කොල', 0, 0, 0),
(16, 'Legal_කොල', 0, 0, 0),
(17, 'පාට_A4_කොල', 0, 0, 0),
(18, 'B5_කොල', 0, 0, 0),
(19, 'ජෙල්_පෑන්', 0, 0, 0),
(20, 'ට්‍රාන්ස්පේරන්සි_කොල', 0, 0, 0),
(21, 'ටැග්ස්', 0, 0, 0),
(22, 'ටිපෙක්ස්', 0, 0, 0),
(23, 'DVD_තැටි', 0, 0, 0),
(24, 'ඩ්‍රෝවින්_පින්_පැකට්', 0, 0, 0),
(25, 'ඩිමයි_කොල', 0, 0, 0),
(26, 'තින්ත_පෑඩ්', 0, 0, 0),
(27, 'තින්ත_බෝතල්_ලොකු', 0, 0, 0),
(28, 'තින්ත_බෝතල්_කුඩා', 0, 0, 0),
(29, 'Note_Sticker', 0, 0, 0),
(30, 'පරි_කඩ_9½x11', 0, 0, 0),
(31, 'පරි_කඩදාසි_15x11', 0, 0, 0),
(32, 'පැන්සල්', 0, 0, 0),
(33, 'Filed_Note_Book', 0, 0, 0),
(34, 'Paper_fastener', 0, 0, 0),
(35, 'Permanet_Pen', 0, 0, 0),
(36, '4_Hole_File', 0, 0, 0),
(37, 'බයින්ඩ්න්_ටේප්', 0, 0, 0),
(38, 'බයින්ඩර්_ගම්_බෝතල්_200ml', 0, 0, 0),
(39, 'බයින්ඩර්_ගම්_බෝතල්_40ml', 0, 0, 0),
(40, 'බුල්ඩෝ_ක්ලිප්ස්', 0, 0, 0),
(41, 'බෝල්පොයින්ට්_පෑන්', 0, 0, 0),
(42, 'මකන_කෑලි', 0, 0, 0),
(43, 'මාකර්_පෙන්', 0, 0, 0),
(44, 'මැනිලා_කඩදාසි', 0, 0, 0),
(45, 'රෙඩ්_ටේප්', 0, 0, 0),
(46, 'ඉටිකොළ_රෝල්', 0, 0, 0),
(47, 'ලිපිකවර_10x7', 0, 0, 0),
(48, 'ලිපිකවර_10x15_ඉන්දියන්_මැනිලා', 0, 0, 0),
(49, 'ලිපිකවර_9x4_ඉන්දියන්_මැනිලා', 0, 0, 0),
(50, 'ලිපිකවර_9x6_සුදු', 0, 0, 0),
(51, 'ලිපිගොනුකවර_මුද්‍රිත', 0, 0, 0),
(52, 'ලිපිගොනුකවර_මුද්‍රිත_නොවන', 0, 0, 0),
(53, 'ලිපිශිර්ෂ_අමාත්‍යාංශය', 0, 0, 0),
(54, 'ලොකු_ගම්_බෝතල්', 0, 0, 0),
(55, 'වයිරස්_ගාඩ්_1user', 0, 0, 0),
(56, 'වයිරස්_ගාඩ්_3user', 0, 0, 0),
(57, 'සටහන්_පත්‍ර', 0, 0, 0),
(58, 'Signing_Pen', 0, 0, 0),
(59, 'ස්ටෙප්ලර්_කටු_24X6', 0, 0, 0),
(60, 'ස්ටෙප්ලර්_කටු_23X8', 0, 0, 0),
(61, 'ස්ටෙප්ලර්_කටු_23X10', 0, 0, 0),
(62, 'ස්ටෙප්ලර්_කටු_23X17', 0, 0, 0),
(63, 'ස්ටෙප්ලර්_කටු_23X15', 0, 0, 0),
(64, 'ස්ටෙප්ලර්_කටු_23X20', 0, 0, 0),
(65, 'ස්ටෙප්ලර්_කටු_23X24', 0, 0, 0),
(66, 'ස්ටෙප්ලර්රි_මුවර්', 0, 0, 0),
(67, 'සෙලෝටේප්½', 0, 0, 0),
(68, 'සෙලෝටේප්_අඟල්1', 0, 0, 0),
(69, 'සෙලෝටේප්_2', 0, 0, 0),
(70, 'ස්ටිකර්_කොල_A4_ප්‍රමාණය', 0, 0, 0),
(71, 'CD_කවර', 0, 0, 0),
(72, 'CD_තැටි', 0, 0, 0),
(73, 'CR_I', 0, 0, 0),
(74, 'CR_II', 0, 0, 0),
(75, 'CR_III', 0, 0, 0),
(76, 'CR_IV', 0, 0, 0),
(77, 'CR_V', 0, 0, 0),
(78, 'හයිලයිට්_පෙන්', 0, 0, 0),
(79, 'හාප්ෂිට්', 0, 0, 0),
(80, 'Trasferance_File_folder', 0, 0, 0),
(81, 'ඩබල්_ටේප්', 0, 0, 0),
(82, 'Toshiba_T_5018_P_e_studio_2518A_printer', 40, 20, 20),
(83, 'Samsun_MLT_D101S_XSG_Fax_Toner', 0, 0, 0),
(84, 'Toshiba_T5018P_e_studio_2518Aprinter', 0, 0, 0),
(85, 'canon_337_Fax_Toner', 0, 0, 0),
(86, 'Canon_IncBottle_canon_pixma_G1010_Printer', 0, 0, 0),
(87, 'HP_HGT52_Colo_inkbottl', 0, 0, 0),
(88, 'Toshiba_T5070_Toner', 0, 0, 0),
(89, 'Brother_TN2360_Toner', 0, 0, 0),
(90, 'Epson_LQ2190_Ribbon', 0, 0, 0),
(91, 'Riso_master_roll_A35_S4277', 0, 0, 0),
(92, 'Riso_ink_Ztype_4251', 0, 0, 0),
(93, 'Canon_LBP_325_Toner', 0, 0, 0),
(94, 'Toshiba_T2309P_Toner', 0, 0, 0),
(95, 'HP_17a_Printer_Toner', 0, 0, 0),
(96, 'Canon_LBP_226_BP_Asta057', 0, 0, 0),
(97, 'Epson_L110_Color_Bottles', 0, 0, 0),
(98, 'Epson_LQ300_II_Ribbon', 0, 0, 0),
(100, 'Brother_HL6200_DN_Toner', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `form_data`
--
ALTER TABLE `form_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`අනු_අංකය`);

--
-- Indexes for table `products_table`
--
ALTER TABLE `products_table`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `form_data`
--
ALTER TABLE `form_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `අනු_අංකය` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products_table`
--
ALTER TABLE `products_table`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`අනු_අංකය`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
