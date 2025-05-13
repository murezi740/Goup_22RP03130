-- Create drivers table
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample drivers
INSERT INTO `drivers` (`name`, `phone`, `vehicle_type`, `vehicle_number`, `is_available`) VALUES
('John Doe', '+250788123456', 'Motorcycle', 'MOTO123', 1),
('Jane Smith', '+250788234567', 'Bicycle', 'BIKE456', 1),
('Robert Johnson', '+250788345678', 'Motorcycle', 'MOTO789', 1); 