import React from 'react';

import Hero from '../components/Hero';

const TermsAndConditions = () => {
  return (
    <>
      <Hero title="Terms" />
      <div className="container pb-5 description">
        <div className="row">
          <div className="col-12 text-center sub-title">
            <h1>Terms and conditions</h1>
          </div>
          <div className="col-lg-12 col-md-12 col-sm-12 mt-4 padding-bottom">
            <p>
              Please read this document carefully before accessing or using this
              site. By accessing or using the site, you agree to be bound by the
              terms and conditions set forth below. If you do not wish to be
              bound by these terms and conditions, you may not access or use the
              site or use the Software.
            </p>
            <p>
              These terms and conditions govern your use of
              https://www.tenniscope.com (site) operated by XXX Ltd (“us”, “we”,
              or “our”).
            </p>
            <p>
              We provide the use and support of Tenniscope (“Betting Software
              For Sale” “Services Subscription” “Technical Support”) to Customer
              (“you”, “your”, “he”, or “his”) under the following Terms and
              Conditions.
            </p>
            <p>
              We may modify this agreement at any time, and such modifications
              shall be effective immediately upon posting of the modified
              agreement on the site. You agree to review the agreement
              periodically to be aware of such modifications and your continued
              access or use of the site shall be deemed your conclusive
              acceptance of the modified agreement.
            </p>
            <h4 className="services-subscription">SERVICES SUBSCRIPTION</h4>
            <p>PAYMENT METHOD, CANCELLATIONS AND PAYMENT FAILED</p>
            <p>
              By subscribing to the Service (Monthly or Yearly Plan) you agree
              to become a subscriber for the period at least 1 (one) month for
              the number of licenses you choose for Bots or Software, for data
              or for system.
            </p>
            <p>
              {
                'Your monthly or yearly subscription for our products will start when we confirms your payment and will continue for a period of the plan purchased. When you purchase a monthly or yearly subscription, auto-renew is automatically selected in your Tenniscope Account. At the end of the subscription period, you will automatically be signed up and billed for an additional subscription term of 1 month or 1 year at then-current pricing. If you would like to discontinue automatic renewal, you may cancel the service via PayPal or through Tenniscope.com>My Account>My Subscriptions prior to the end of your current subscription term. Once your subscription is cancelled, no further recurring payment on your account will be made and then a final confirmation will be sent by e-mail. Once the subscription has been cancelled the access to the software, bot, data, betting system stops at the end of that current billing cycle.'
              }
            </p>
            <p>
              By default auto renewal is turned on as the service starts and it
              is a client’s responsibility to cancel their reccuring payment
              with PayPal in case of payment failed. Any payments received
              because a client has not cancelled their subscription will not be
              refunded. If for any reason payment is not received by us on the
              billing date each month we reserves the right to restrict services
              and if the payment remains outstanding after 3 days after the
              payment date, we will cancel the service.
            </p>
          </div>
        </div>
      </div>
    </>
  );
};

export default TermsAndConditions;
