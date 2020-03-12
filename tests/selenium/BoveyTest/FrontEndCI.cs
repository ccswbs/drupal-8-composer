using System;
using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;

namespace BoveyTest
{
    [TestClass]
    public class FrontEndCI : DrupalTest
    {
        [TestInitialize]
        [DeploymentItem("appsettings*.json")]
        public void Initialize()
        {
            var config = new ConfigurationBuilder()
                .AddJsonFile("appsettings.json")
                .AddJsonFile("appsettings.local.json", optional:true)
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
        public void BuildTriggerSuccessful()
        {
            var frontEndEnv = "stage_gatsby_frontend_environment";
            var buildTriggerSubmitBtn = "edit-submit";
            
            // Go to Build Trigger deployment page
            DrupalGet("/admin/build_hooks/deployments/" + frontEndEnv);

            // Activate build trigger
            Click(buildTriggerSubmitBtn);

            // Check if successful message appears after testing connection
            var successfulBuildTriggerMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'Deployment triggered for environment')]]");
            Assert.AreEqual(successfulBuildTriggerMessage.Count, 1);
        }

        [TestMethod]
        public void BuildTriggerOnlyVisibleForPublisherAndAdmin()
        {
            var permissionIDStub = "trigger-deployments";
            string[] permittedRoles = new string[] {"administrator", "publisher"};
            
            // Go to Permissions page
            DrupalGet("/admin/people/permissions");

            // Check if permitted roles have permission
            foreach (string role in permittedRoles){
                var adminPermission = Driver.FindElementsByXPath($"//input[contains(@id, 'edit-{role}-{permissionIDStub}') and contains(@checked, 'checked')]");
                Assert.AreEqual(adminPermission.Count, 1);
            }

            // Check that no other roles have permission
            var numberOfRolesWithPermission = Driver.FindElementsByXPath($"//input[contains(@id, '-{permissionIDStub}') and contains(@checked, 'checked')]");
            Assert.AreEqual(numberOfRolesWithPermission.Count, permittedRoles.Length);
        }
    }
}
