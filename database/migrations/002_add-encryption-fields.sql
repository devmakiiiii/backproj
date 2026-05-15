-- Add encryption fields to users table
ALTER TABLE users 
  ADD COLUMN email_iv VARBINARY(12) NULL,
  ADD COLUMN email_tag VARBINARY(16) NULL,
  ADD COLUMN phone_iv VARBINARY(12) NULL,
  ADD COLUMN phone_tag VARBINARY(16) NULL;

-- Add encryption fields to auction_items table
ALTER TABLE auction_items 
  ADD COLUMN description_iv VARBINARY(12) NULL,
  ADD COLUMN description_tag VARBINARY(16) NULL;

-- Add encryption fields to bids table
ALTER TABLE bids 
  ADD COLUMN amount_iv VARBINARY(12) NULL,
  ADD COLUMN amount_tag VARBINARY(16) NULL;