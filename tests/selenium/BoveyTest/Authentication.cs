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
            base.Initialize(config["hostname"], config["basePath"]);
            DrupalLogin(config["username"], config["password"]);
        }

        [TestMethod]
        public void TestLDAPConnection()
        {
            var ldapTestUserName = "wsadmin";
            var editAuthenticationNameField = "edit-testing-drupal-username";
            var editAuthenticationSubmitBtn = "edit-submit";
            
            // Go to LDAP Send test queries page
            DrupalGet("/admin/config/people/ldap/server/ug/test");
            Check(editAuthenticationNameField);
            Type(editAuthenticationNameField, ldapTestUserName);
            Click(editAuthenticationSubmitBtn);
            var userTokenSamples = Driver.FindElementsByXPath($"//h2[contains(text()[2], 'User Token Samples')]");
            Assert.AreEqual(userTokenSamples.Count, 0);
        }
    }
}
