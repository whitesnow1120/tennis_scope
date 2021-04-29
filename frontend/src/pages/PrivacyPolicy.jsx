import React from 'react';

import Hero from '../components/Hero';

const PrivacyPolicy = () => {
  return (
    <>
      <Hero title="Privacy" />
      <div className="container pb-5 description">
        <div className="row">
          <div className="col-12 text-center sub-title">
            <h1>Privacy policy</h1>
          </div>
          <div className="col-lg-12 col-md-12 col-sm-12 mt-4 padding-bottom">
            <p>Welcome to Tenniscope.com’s Privacy Policy</p>
            <p>
              This privacy policy sets out how Tenniscope.com uses and protects
              any information that you give us when you use this website.
            </p>
            <p>
              Tenniscope.com is committed to ensuring that your privacy is
              protected. Should we ask you to provide certain information by
              which you can be identified when using this website, then you can
              be assured that it will only be used in accordance with this
              privacy statement.
            </p>
            <h4 className="personal-information-collect">
              The type of personal information we collect
            </h4>
            <p>
              We collect certain personal information about visitors and users
              of Tenniscope.com.
            </p>
            <p>
              The most common types of information we collect include things
              like: First names, Last names, Usernames, email addresses,
              Country, IP addresses, Facebook username, survey responses,
              avatar, payment information such as payment processor,
              transactional details, support queries, forum comments,
              testimonials, content you send us (such as descriptions of betting
              strategies) and web analytics data.
            </p>
            <h4 className="non-personal-identification">
              How we collect non-personal identification information and
              personal information
            </h4>
            <p>
              We collect non-personal identification information automatically
              as you navigate through Tenniscope.com. We may collect about
              browser name, the type of computer and technical information about
              users means of connection to our site, such as the operating
              system and the Internet service providers utilized and other
              similar information.
            </p>
            <p>
              We collect your personal information when you provide it to us
              when you complete membership registration, buy or trial one of our
              products, subscribe to one of our services, subscribe to our
              newsletter, email list, submit feedback, enter a contest, fill out
              a survey or send us a message through our forms.
            </p>
            <p>
              When you register on our site you are automatically registered for
              the Tenniscope newsletter, a regular source of news, information
              and ideas. But you can, of course, unsubscribe when you wish
              directly through the newsletter you receive or by asking us.
            </p>
          </div>
        </div>
      </div>
    </>
  );
};

export default PrivacyPolicy;
