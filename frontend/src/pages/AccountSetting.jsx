import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';

import AccountInput from '../components/AccountInput';
import AccountToggle from '../components/AccountToggle';

const AccountSetting = () => {
  const { accountInfo } = useSelector((state) => state.tennis);

  const [value, setValue] = useState({
    title: '',
    val: '',
    changed: false,
    opened: false,
  });

  useEffect(() => {
    if (value.changed) {
      alert(value);
    }
    setValue({
      title: '',
      val: '',
      changed: false,
      opened: false,
    });
  }, [value.changed]);

  return (
    <>
      <div className="account-setting">
        <h3>Account Settings</h3>
        <div className="sub-settings">
          <div className="account-setting-sub-title">General Settings</div>
          <AccountInput
            title="Name"
            defaultValue={accountInfo.name}
            placeholder="Write your name..."
            value={value}
            setValue={setValue}
          />
          <AccountInput
            title="Surname"
            defaultValue={accountInfo.surname}
            placeholder="Write your surname..."
            value={value}
            setValue={setValue}
          />
          <AccountInput
            title="E-mail"
            defaultValue={accountInfo.mail}
            placeholder="Write your e-mail..."
            value={value}
            setValue={setValue}
          />
          <AccountToggle
            title="Show tooltips"
            name="showTooltips"
            checked={accountInfo.showTooltips}
            accountInfo={accountInfo}
          />
        </div>
        <div className="sub-settings">
          <div className="account-setting-sub-title">Notification settings</div>
          <AccountToggle
            title="Subscription notifications"
            name="subscriptionNotifications"
            checked={accountInfo.subscriptionNotifications}
            accountInfo={accountInfo}
          />
          <AccountToggle
            title="New features"
            name="newFeatures"
            checked={accountInfo.newFeatures}
            accountInfo={accountInfo}
          />
          <AccountToggle
            title="promotion notifications"
            name="promotionNotifications"
            checked={accountInfo.promotionNotifications}
            accountInfo={accountInfo}
          />
        </div>
        <div className="sub-settings">
          <div className="account-setting-sub-title">Subscriptions</div>
        </div>
      </div>
    </>
  );
};

export default AccountSetting;
