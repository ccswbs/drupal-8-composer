using System;
using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;

namespace BoveyTest
{
    [TestClass]
    public class Authentication : DrupalTest
    {
        [TestInitialize]
        [DeploymentItem("appsettings.json")]
        public void Initialize()
        {
            var config = new ConfigurationBuilder()
                .AddJsonFile("appsettings.json")
                .Build();
            base.Initialize(config["TestQAHostname"], config["basePath"]);
            DrupalLogin(config["TestQAUsername"], config["TestQAPassword"]);
        }

        [TestCleanup]
        override public void Cleanup()
        {
            _driver.Quit();
        }

        [TestMethod]
        public void TestLDAPConnection()
        {
            var ldapTestUserName = "wsadmin";
            var editAuthenticationNameField = "edit-testing-drupal-username";
            var editAuthenticationSubmitBtn = "edit-submit";
            
            // Go to LDAP Send test queries page
            DrupalGet("/admin/config/people/ldap/server/ug/test");

            // Test connection using test LDAP user name
            Check(editAuthenticationNameField);
            Type(editAuthenticationNameField, ldapTestUserName);
            Click(editAuthenticationSubmitBtn);

            // Check if successful message appears after testing connection
            var successfulLDAPConnectionMessage = Driver.FindElementsByXPath($"//table/tr/td[contains(text(), 'Successfully bound to server')]");
            Assert.AreEqual(successfulLDAPConnectionMessage.Count, 0);
        }
    }
}
