# Magento 2 Rest PaypalApi Module

    ``Gaggle/module-paypalapi``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Gaggle_GiftcardApi

## Installation
 = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Gaggle`
 - Enable the module by running `php bin/magento module:enable Gaggle_PaypalApi`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`

## Specifications

 - API Endpoint
	- POST - Gaggle\PaypalApi\Api\CreatePaypalExpressTokenManagementInterface > Gaggle\PaypalApi\Model\CreatePaypalExpressTokenManagement

 - API Endpoint
	- POST - Gaggle\PaypalApi\Api\SetPaymentMethodOnCartManagementInterface > Gaggle\PaypalApi\Model\SetPaymentMethodOnCartManagement

 - API Endpoint
	- POST - Gaggle\PaypalApi\Api\PlaceOrderManagementInterface > Gaggle\PaypalApi\Model\PlaceOrderManagement


## PayPal Rest API Documentation


For the Place order by the Paypal rest api you need a active cart with shipping and billing address

By default we need to follow the few setups for placing the order


    • Step 1. Create an empty cart

    • Step 2. Add products to the cart

    • Step 3. Set the shipping address

    • Step 4. Set billing address

    • Step 5. Set the delivery method

    • Step 6. Apply a coupon (if you have)

    • Step 7. Set the payment method

    • Step 8. Place order


- After steps 6 follow below APIs 

- We need to call below APIs one by one for placing the order with Paypal



## Create Paypal Express Token

- URL : {you website url}/rest/default/V1/paypalapi/createpaypalexpresstoken

- Method : POST

- Set Bearer Token in the Hearer  (if customer ) for guest user no need to set it

- Content-type : JSON

- Body For guest user :

- Body : 

{
   "cart_id":"5QWFYZdyccucvgD2QMLDCp5fhjmaH2xg",
   "cancel_url":"cancel_url",
   "return_url":"return_url"
}

- Body For Customer user:

- Body : 

{
   "cart_id": 22,
   "cancel_url": "cancel_url",
   "return_url": "return_url"
}

- You wil get the response like this : 

[
   {
       "code": 200,
       "token": "EC-4MD50688YD296870K",
       "paypal_urls": {
            "start": "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-4MD50688YD296870K&useraction=commit",
     	    "edit": "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=continue&token=EC-4MD50688YD296870K"
  	}
  }
]
-------------------------------------------------------------------------------------------------------------------------------------------------
- Explanation – you need to redirect the customer to the {start url } and  after make the payment, paypal will redirect the user with two params in the redirect url "payer_id" and "token" Both value you need to call in the below api 



## Set Payment Method On Cart

- URL : {you website url}/rest/default/V1/paypalapi/setpaymentmethodoncart


- Method : POST

- Set Bearer Token in the Hearer  (if customer ) for guest user no need to set it

- Content-type : JSON

- Body For guest user :

- Body : 

{
   "cart_id": "5QWFYZdyccucvgD2QMLDCp5fhjmaH2xg",
   "payer_id": "9T3GV67ZSL378",
   "token": "EC-4MD50688YD296870K",
   "payment_method": "paypal_express"
 }


- Body For Customer user:

- Body : 

{
   "cart_id": 22,
   "payer_id": "9T3GV67ZSL378",
   "token": "EC-4MD50688YD296870K",
   "payment_method": "paypal_express",
   "customer_id": 141
 }

- You will get the response like this : 

[
   {
      "code": 200,
      "selected_payment_method": {
      "code": "paypal_express",
      "title": "PayPal Express Checkout"
      }
   }
]



## Place Order


- URL : {you website url}/rest/default/V1/paypalapi/placeorder

- Method : POST

- Set Bearer Token in the Hearer  (if customer ) for guest user no need to set it

- Content-type :  JSON



- Body For guest user :

- Body : 

{
   "cart_id": "5QWFYZdyccucvgD2QMLDCp5fhjmaH2xg"
}



- Body For Customer user:

- Body : 

{
   "cart_id": 22,
   "customer_id": 141
}




- You will get the response like this : 

[
   {
      "code": 200,
      "order_number": 000000142
   }
]
